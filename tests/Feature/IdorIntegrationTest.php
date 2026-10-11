<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssertsPrivateResponse;
use Tests\Support\IntegrationFixture;
use Tests\TestCase;

class IdorIntegrationTest extends TestCase
{
    use AssertsPrivateResponse, IntegrationFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createIntegrationFixture();
    }

    private function assertNoTargetData(TestResponse $response, int $target): void
    {
        $secrets = [$this->courses[$target]->name, $this->materials[$target]->title,
            $this->materials[$target]->description, $this->materials[$target]->file_path,
            $this->assignments[$target]->instructions, $this->submissions[$target]->note,
            $this->submissions[$target]->file_path, $this->students[$target]->email,
            'Feedback privat'];
        $this->assertResponseExcludesSecrets($response, $secrets);
        if ($response->status() === 403 && str_starts_with($response->baseRequest?->getPathInfo() ?? '', '/api/')) {
            $response->assertExactJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.'])
                ->assertJsonMissingPath('data');
        }
    }

    private function assertAuthorizedResource(TestResponse $response, string $kind, int $actor): void
    {
        $course = $this->courses[$actor];
        $material = $this->materials[$actor];
        $assignment = $this->assignments[$actor];
        $submission = $this->submissions[$actor];
        $isList = in_array($kind, ['materials', 'assignments', 'submissions'], true);
        $resource = match ($kind) {
            'course' => $course,
            'material', 'materials' => $material,
            'assignment', 'assignments' => $assignment,
            'submission', 'submissions' => $submission,
        };
        $response->assertOk();
        if (str_contains($response->headers->get('Content-Type', ''), 'json')) {
            $prefix = $isList ? 'data.0.' : 'data.';
            if ($isList) {
                $response->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);
            }
            $expected = match ($kind) {
                'course' => ['id' => $course->id, 'code' => $course->code, 'lecturer.id' => $this->lecturers[$actor]->id],
                'material', 'materials' => ['id' => $material->id, 'course_id' => $course->id,
                    'title' => $material->title, 'uploader.id' => $this->lecturers[$actor]->id, 'file_path' => $material->file_path],
                'assignment', 'assignments' => ['id' => $assignment->id, 'course_id' => $course->id,
                    'title' => $assignment->title, 'instructions' => $assignment->instructions],
                'submission', 'submissions' => ['id' => $submission->id, 'assignment_id' => $assignment->id,
                    'user_id' => $this->students[$actor]->id, 'student.id' => $this->students[$actor]->id,
                    'note' => $submission->note, 'file_path' => $submission->file_path],
            };
            foreach ($expected as $key => $value) {
                $response->assertJsonPath($prefix.$key, $value);
            }
        } else {
            $view = match ($kind) {
                'materials' => 'materials.index', 'material' => 'materials.show',
                'assignments' => 'assignments.index', 'assignment' => 'assignments.show',
                'submissions' => 'submissions.index', 'submission' => 'submissions.show',
            };
            $response->assertViewIs($view)->assertViewHas('course', fn ($actual) => $actual->id === $course->id);
            if ($isList) {
                $response->assertViewHas($kind, fn ($actual) => $actual->modelKeys() === [$resource->id]);
            } else {
                $expected = match ($kind) {
                    'material' => ['id' => $material->id, 'course_id' => $course->id, 'uploaded_by' => $this->lecturers[$actor]->id],
                    'assignment' => ['id' => $assignment->id, 'course_id' => $course->id, 'created_by' => $this->lecturers[$actor]->id],
                    'submission' => ['id' => $submission->id, 'assignment_id' => $assignment->id, 'user_id' => $this->students[$actor]->id],
                };
                $response->assertViewHas($kind, function ($actual) use ($expected) {
                    foreach ($expected as $key => $value) {
                        if ($actual->{$key} !== $value) {
                            return false;
                        }
                    }

                    return true;
                });
            }
            $response->assertSee($resource->title ?? $resource->original_name);
        }
        $this->assertNoTargetData($response, 1 - $actor);
    }

    #[DataProvider('directions')]
    public function test_student_submission_ownership_in_shared_course(int $actor, int $target): void
    {
        $this->actingAs($this->students[$actor]);
        $before = $this->databaseSnapshot();
        $this->get(route('mahasiswa.submissions.show', $this->privateSubmissions[$actor]))
            ->assertOk()->assertViewIs('submissions.show')
            ->assertViewHas('submission', fn ($actual) => $actual->id === $this->privateSubmissions[$actor]->id
                && $actual->user_id === $this->students[$actor]->id
                && $actual->assignment_id === $this->sharedAssignment->id)
            ->assertViewHas('course', fn ($actual) => $actual->id === $this->sharedCourse->id)
            ->assertSee($this->privateSubmissions[$actor]->note)
            ->assertDontSee($this->privateSubmissions[$target]->note);
        $foreign = $this->privateSubmissions[$target];
        $this->get(route('mahasiswa.submissions.show', $foreign))
            ->assertForbidden()->assertDontSee($foreign->note)
            ->assertDontSee($foreign->original_name)->assertDontSee($this->students[$target]->email);
        $this->assertSame($before, $this->databaseSnapshot());
    }

    #[DataProvider('directions')]
    public function test_students_read_enrolled_resources_but_cannot_swap_course_or_resource_id(int $actor, int $target): void
    {
        $this->actingAs($this->students[$actor]);
        Sanctum::actingAs($this->students[$actor]);
        $before = $this->databaseSnapshot();
        $coursesResponse = $this->getJson('/api/v1/courses')->assertOk()->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $this->courses[$actor]->id)
            ->assertJsonPath('data.0.lecturer.id', $this->lecturers[$actor]->id)
            ->assertJsonPath('data.1.id', $this->sharedCourse->id)
            ->assertJsonMissing(['id' => $this->courses[$target]->id, 'code' => $this->courses[$target]->code]);
        $this->assertNoTargetData($coursesResponse, $target);
        foreach ([$actor, $target] as $index) {
            $paths = [
                ['/api/v1/courses/'.$this->courses[$index]->id, 'course'],
                ['/api/v1/courses/'.$this->courses[$index]->id.'/materials', 'materials'],
                ['/api/v1/courses/'.$this->courses[$index]->id.'/assignments', 'assignments'],
                [route('mahasiswa.courses.materials.index', $this->courses[$index]), 'materials'],
                [route('mahasiswa.materials.show', $this->materials[$index]), 'material'],
                [route('mahasiswa.courses.assignments.index', $this->courses[$index]), 'assignments'],
                [route('mahasiswa.assignments.show', $this->assignments[$index]), 'assignment'],
            ];
            foreach ($paths as [$path, $kind]) {
                $response = str_contains($path, '/api/') ? $this->getJson($path) : $this->get($path);
                if ($index === $actor) {
                    $this->assertAuthorizedResource($response, $kind, $actor);
                } else {
                    $response->assertForbidden();
                    $this->assertNoTargetData($response, $target);
                }
            }
        }
        // A forged query FK must not replace the authorized route-bound course.
        $queryResponse = $this->getJson('/api/v1/courses/'.$this->courses[$actor]->id.'/materials?course_id='.$this->courses[$target]->id);
        $this->assertAuthorizedResource($queryResponse, 'materials', $actor);
        $this->assertSame($before, $this->databaseSnapshot());
    }

    #[DataProvider('directions')]
    public function test_student_role_cannot_create_update_delete_or_grade(int $actor, int $target): void
    {
        $this->actingAs($this->students[$actor]);
        Sanctum::actingAs($this->students[$actor]);
        $before = $this->databaseSnapshot();
        $requests = [
            ['POST', route('dosen.courses.materials.store', $this->courses[$target]), $this->materialPayload()],
            ['PUT', route('dosen.materials.update', $this->materials[$target]), $this->materialPayload()],
            ['DELETE', route('dosen.materials.destroy', $this->materials[$target]), []],
            ['POST', route('dosen.courses.assignments.store', $this->courses[$target]), $this->assignmentPayload($this->courses[$target])],
            ['PUT', route('dosen.assignments.update', $this->assignments[$target]), $this->assignmentPayload($this->courses[$target])],
            ['DELETE', route('dosen.assignments.destroy', $this->assignments[$target]), []],
            ['POST', '/api/v1/assignments', $this->assignmentPayload($this->courses[$target])],
            ['PUT', '/api/v1/assignments/'.$this->assignments[$target]->id, ['title' => 'Terlarang']],
            ['PATCH', '/api/v1/assignments/'.$this->assignments[$target]->id, ['status' => 'draft']],
            ['DELETE', '/api/v1/assignments/'.$this->assignments[$target]->id, []],
            ['PUT', '/api/v1/submissions/'.$this->submissions[$target]->id.'/grade', ['score' => 80]],
            ['GET', '/api/v1/assignments/'.$this->assignments[$actor]->id.'/submissions', []],
            ['POST', '/admin/users', ['name' => 'Injected', 'role' => 'admin']],
            ['PUT', '/admin/users/'.$this->students[$target]->id, ['role' => 'admin']],
            ['DELETE', '/admin/users/'.$this->students[$target]->id, []],
        ];
        foreach ($requests as [$method, $url, $payload]) {
            $response = $this->json($method, $url, $payload)->assertForbidden();
            $this->assertNoTargetData($response, $target);
            $this->assertSame($before, $this->databaseSnapshot());
        }
        Storage::disk('local')->assertExists($this->materials[$target]->file_path);
        Storage::disk('local')->assertExists($this->submissions[$target]->file_path);
    }

    #[DataProvider('directions')]
    public function test_lecturer_reads_only_assigned_course_resources(int $actor, int $target): void
    {
        $this->actingAs($this->lecturers[$actor]);
        Sanctum::actingAs($this->lecturers[$actor]);
        $before = $this->databaseSnapshot();
        $coursesResponse = $this->getJson('/api/v1/courses')->assertOk()->assertJsonPath('meta.total', $actor === 0 ? 2 : 1)
            ->assertJsonPath('data.0.id', $this->courses[$actor]->id)
            ->assertJsonPath('data.0.lecturer.id', $this->lecturers[$actor]->id);
        $expectedIds = $actor === 0 ? [$this->courses[$actor]->id, $this->sharedCourse->id] : [$this->courses[$actor]->id];
        $this->assertSame($expectedIds, array_column($coursesResponse->json('data'), 'id'));
        $this->assertNoTargetData($coursesResponse, $target);
        foreach ([$actor, $target] as $index) {
            foreach ([
                ['/api/v1/courses/'.$this->courses[$index]->id, 'course'],
                ['/api/v1/courses/'.$this->courses[$index]->id.'/materials', 'materials'],
                ['/api/v1/courses/'.$this->courses[$index]->id.'/assignments', 'assignments'],
                ['/api/v1/assignments/'.$this->assignments[$index]->id.'/submissions', 'submissions'],
                [route('dosen.courses.materials.index', $this->courses[$index]), 'materials'],
                [route('dosen.materials.show', $this->materials[$index]), 'material'],
                [route('dosen.courses.assignments.index', $this->courses[$index]), 'assignments'],
                [route('dosen.assignments.show', $this->assignments[$index]), 'assignment'],
                [route('dosen.assignments.submissions.index', $this->assignments[$index]), 'submissions'],
                [route('dosen.submissions.show', $this->submissions[$index]), 'submission'],
            ] as [$url, $kind]) {
                $response = str_contains($url, '/api/') ? $this->getJson($url) : $this->get($url);
                if ($index === $actor) {
                    $this->assertAuthorizedResource($response, $kind, $actor);
                } else {
                    $response->assertForbidden();
                    $this->assertNoTargetData($response, $target);
                }
            }
        }
        foreach ([route('dosen.courses.materials.create', $this->courses[$target]),
            route('dosen.materials.edit', $this->materials[$target]),
            route('dosen.courses.assignments.create', $this->courses[$target]),
            route('dosen.assignments.edit', $this->assignments[$target])] as $url) {
            $this->assertNoTargetData($this->get($url)->assertForbidden(), $target);
        }
        $this->assertSame($before, $this->databaseSnapshot());
    }

    #[DataProvider('directions')]
    public function test_lecturer_foreign_writes_preserve_database_grades_and_files(int $actor, int $target): void
    {
        $this->actingAs($this->lecturers[$actor]);
        Sanctum::actingAs($this->lecturers[$actor]);
        Grade::create([
            'submission_id' => $this->submissions[$target]->id, 'graded_by' => $this->lecturers[$target]->id,
            'score' => 75, 'feedback' => 'Feedback privat', 'graded_at' => now(),
        ]);
        $before = $this->databaseSnapshot();
        $requests = [
            ['POST', route('dosen.courses.materials.store', $this->courses[$target]), $this->materialPayload()],
            ['PUT', route('dosen.materials.update', $this->materials[$target]), $this->materialPayload()],
            ['DELETE', route('dosen.materials.destroy', $this->materials[$target]), []],
            ['POST', route('dosen.courses.assignments.store', $this->courses[$target]), $this->assignmentPayload($this->courses[$target])],
            ['PUT', route('dosen.assignments.update', $this->assignments[$target]), $this->assignmentPayload($this->courses[$target])],
            ['DELETE', route('dosen.assignments.destroy', $this->assignments[$target]), []],
            ['POST', '/api/v1/assignments', $this->assignmentPayload($this->courses[$target])],
            ['PUT', '/api/v1/assignments/'.$this->assignments[$target]->id, ['title' => 'Terlarang']],
            ['PATCH', '/api/v1/assignments/'.$this->assignments[$target]->id, ['status' => 'draft']],
            ['DELETE', '/api/v1/assignments/'.$this->assignments[$target]->id, []],
            ['PUT', '/api/v1/submissions/'.$this->submissions[$target]->id.'/grade', ['score' => 90, 'feedback' => 'Terlarang']],
            ['POST', '/api/v1/assignments/'.$this->assignments[$actor]->id.'/submissions', []],
            ['POST', '/admin/courses', $this->courses[$target]->only(['code', 'name', 'sks', 'lecturer_id', 'status'])],
            ['PUT', '/admin/courses/'.$this->courses[$target]->id, ['lecturer_id' => $this->lecturers[$actor]->id]],
            ['DELETE', '/admin/courses/'.$this->courses[$target]->id, []],
        ];
        foreach ($requests as [$method, $url, $payload]) {
            $response = $this->json($method, $url, $payload)->assertForbidden();
            $this->assertNoTargetData($response, $target);
            $response->assertDontSee('Feedback privat', false);
            $this->assertSame($before, $this->databaseSnapshot());
        }
        // Malformed updates must still authorize the target before validation.
        $this->patchJson('/api/v1/assignments/'.$this->assignments[$target]->id, ['due_at' => 'invalid'])->assertForbidden();
        $this->assertSame($before, $this->databaseSnapshot());
        $this->assertSame('Berkas materi '.($target === 0 ? 'A' : 'B'), Storage::disk('local')->get($this->materials[$target]->file_path));
        Storage::disk('local')->assertExists($this->submissions[$target]->file_path);
    }

    #[DataProvider('directions')]
    public function test_lecturer_ownership_payload_cannot_move_resources_to_foreign_course(int $actor, int $target): void
    {
        $this->actingAs($this->lecturers[$actor]);
        Sanctum::actingAs($this->lecturers[$actor]);
        $foreignBefore = $this->courses[$target]->fresh()->getAttributes();
        $forged = ['course_id' => $this->courses[$target]->id, 'created_by' => $this->lecturers[$target]->id,
            'uploaded_by' => $this->lecturers[$target]->id];
        $response = $this->postJson('/api/v1/assignments', array_replace(
            $this->assignmentPayload($this->courses[$actor]), ['created_by' => $this->lecturers[$target]->id]
        ))->assertCreated()->assertJsonPath('data.course_id', $this->courses[$actor]->id);
        $assignment = Assignment::findOrFail($response->json('data.id'));
        $response->assertJsonPath('data.id', $assignment->id)->assertJsonPath('data.title', $assignment->title);
        $this->assertEquals($this->lecturers[$actor]->id, $assignment->created_by);
        $this->patchJson('/api/v1/assignments/'.$assignment->id, ['title' => 'Revisi sah'] + $forged)
            ->assertOk()->assertJsonPath('data.id', $assignment->id)
            ->assertJsonPath('data.course_id', $this->courses[$actor]->id)->assertJsonPath('data.title', 'Revisi sah');
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'created_by' => $this->lecturers[$actor]->id,
            'course_id' => $this->courses[$actor]->id, 'title' => 'Revisi sah']);
        $this->post(route('dosen.courses.materials.store', $this->courses[$actor]), $this->materialPayload() + $forged)
            ->assertRedirect(route('dosen.courses.materials.index', $this->courses[$actor]))->assertSessionHasNoErrors();
        $material = Material::where('title', 'Materi audit baru')->sole();
        $this->assertEquals($this->courses[$actor]->id, $material->course_id);
        $this->assertEquals($this->lecturers[$actor]->id, $material->uploaded_by);
        $this->put(route('dosen.materials.update', $material), $this->materialPayload() + $forged)
            ->assertRedirect(route('dosen.materials.show', $material))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'course_id' => $this->courses[$actor]->id,
            'uploaded_by' => $this->lecturers[$actor]->id]);
        $this->assertSame($foreignBefore, $this->courses[$target]->fresh()->getAttributes());
    }

    #[DataProvider('directions')]
    public function test_student_submission_payload_uses_authenticated_user_and_route_assignment(int $actor, int $target): void
    {
        $this->actingAs($this->students[$actor]);
        Sanctum::actingAs($this->students[$actor]);
        $assignment = $this->fixtureAssignment($this->sharedCourse);
        $foreignBefore = $this->submissions[$target]->fresh()->getAttributes();
        $response = $this->post('/api/v1/assignments/'.$assignment->id.'/submissions', [
            'file' => UploadedFile::fake()->create('audit.pdf', 10, 'application/pdf'), 'note' => 'Sah',
            'user_id' => $this->students[$target]->id, 'assignment_id' => $this->assignments[$target]->id,
            'course_id' => $this->courses[$target]->id,
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.user_id', $this->students[$actor]->id)
            ->assertJsonPath('data.student.id', $this->students[$actor]->id)
            ->assertJsonPath('data.student.email', $this->students[$actor]->email)
            ->assertJsonPath('data.assignment_id', $assignment->id);
        $submission = Submission::findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($submission->file_path);
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'user_id' => $this->students[$actor]->id,
            'assignment_id' => $assignment->id]);
        $this->assertSame($foreignBefore, $this->submissions[$target]->fresh()->getAttributes());
        $before = $this->databaseSnapshot();
        $filesBefore = Storage::disk('local')->allFiles();
        // Replacing the route ID with the foreign assignment cannot create files or rows.
        foreach (['/api/v1/assignments/'.$this->assignments[$target]->id.'/submissions',
            route('mahasiswa.assignments.submissions.store', $this->assignments[$target])] as $url) {
            $this->post($url, ['file' => UploadedFile::fake()->create('forbidden.pdf', 10),
                'user_id' => $this->students[$target]->id, 'assignment_id' => $assignment->id],
                ['Accept' => 'application/json'])->assertForbidden();
            $this->assertSame($before, $this->databaseSnapshot());
            $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        }
    }

    public static function notificationActors(): array
    {
        return ['student A to B' => ['students', 0, 1], 'student B to A' => ['students', 1, 0],
            'lecturer A to B' => ['lecturers', 0, 1], 'lecturer B to A' => ['lecturers', 1, 0]];
    }

    #[DataProvider('notificationActors')]
    public function test_notification_uuid_is_authorized_for_each_of_four_identities(string $group, int $actor, int $target): void
    {
        $owner = $this->{$group}[$actor];
        $foreignOwner = $this->{$group}[$target];
        $own = $this->fixtureNotification($owner);
        $foreign = $this->fixtureNotification($foreignOwner);
        Sanctum::actingAs($owner);
        $before = $this->databaseSnapshot();
        $list = $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own)->assertJsonPath('data.0.data.message', 'Notifikasi privat '.$owner->name);
        $this->assertResponseExcludesSecrets($list, [$foreign, 'Notifikasi privat '.$foreignOwner->name]);
        $this->postJson('/api/v1/notifications/'.$foreign.'/read')->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
        $this->assertSame($before, $this->databaseSnapshot());
        $this->postJson('/api/v1/notifications/'.$own.'/read')->assertOk()->assertJsonPath('data.id', $own)
            ->assertJsonPath('data.data.message', 'Notifikasi privat '.$owner->name);
        $this->assertNotNull($owner->notifications()->findOrFail($own)->read_at);
        $this->assertNull($foreignOwner->notifications()->findOrFail($foreign)->read_at);
    }
}
