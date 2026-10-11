<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function metadataScenario(): array
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $owner->id, 'status' => 'draft']);
        Sanctum::actingAs($owner);

        return [$owner, $course, $assignment];
    }

    private function metadataPayload(Course $course): array
    {
        return ['course_id' => $course->id, 'title' => 'Metadata API', 'instructions' => 'Kerjakan soal', 'due_at' => now()->addWeek()->toDateTimeString(), 'max_score' => 100, 'allow_late' => false, 'status' => 'published'];
    }

    public static function textAtByteLimit(): array
    {
        return [
            'ASCII 65535 bytes' => [str_repeat('a', 65535)],
            'UTF-8 65535 bytes' => [str_repeat('é', 32767).'a'],
        ];
    }

    #[DataProvider('textAtByteLimit')]
    public function test_api_instructions_at_byte_limit_are_saved_on_create_and_update(string $text): void
    {
        [, $course, $assignment] = $this->metadataScenario();
        $payload = array_replace($this->metadataPayload($course), ['instructions' => $text]);
        $response = $this->postJson('/api/v1/assignments', $payload)->assertCreated();
        $created = Assignment::findOrFail($response->json('data.id'));
        $this->assertSame($text, $created->instructions);

        $this->patchJson("/api/v1/assignments/{$assignment->id}", ['instructions' => $text])->assertOk();
        $this->assertSame($text, $assignment->fresh()->instructions);
    }

    public static function oversizedText(): array
    {
        return [
            'ASCII 65536 bytes' => [str_repeat('a', 65536)],
            'UTF-8 65536 bytes' => [str_repeat('é', 32768)],
        ];
    }

    #[DataProvider('oversizedText')]
    public function test_oversized_api_instructions_are_rejected_without_writes_on_create_and_update(string $text): void
    {
        [, $course, $assignment] = $this->metadataScenario();
        $original = $assignment->fresh()->getAttributes();
        $payload = array_replace($this->metadataPayload($course), ['instructions' => $text]);

        $this->postJson('/api/v1/assignments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data yang diberikan tidak valid.')
            ->assertJsonValidationErrors('instructions');
        $this->assertDatabaseCount('assignments', 1);
        $this->patchJson("/api/v1/assignments/{$assignment->id}", ['instructions' => $text, 'title' => 'Tidak boleh disimpan'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data yang diberikan tidak valid.')
            ->assertJsonValidationErrors('instructions');
        $this->assertSame($original, $assignment->fresh()->getAttributes());
    }

    public function test_students_never_receive_drafts_even_with_status_filter_or_pagination(): void
    {
        [$owner, $course, $draft] = $this->metadataScenario();
        $published = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $owner->id, 'status' => 'published']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($course, ['enrolled_at' => now()]);
        Sanctum::actingAs($student);
        $endpoint = "/api/v1/courses/{$course->id}/assignments";
        foreach (['', '?status=published'] as $query) {
            $this->getJson($endpoint.$query)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $published->id)->assertJsonPath('meta.total', 1);
        }
        $this->getJson($endpoint.'?status=draft')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
        $this->getJson($endpoint.'?page=2')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 1);
        // Tidak ada endpoint detail tugas API pada route proyek ini.
        $this->getJson("/api/v1/assignments/{$draft->id}")->assertStatus(405);
    }

    public function test_course_api_counts_only_published_assignments_for_students_but_all_for_owner_and_admin(): void
    {
        [$owner, $course] = $this->metadataScenario();
        Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $owner->id, 'status' => 'published']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($course, ['enrolled_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([[$student, 1], [$owner, 2], [$admin, 2]] as [$user, $count]) {
            Sanctum::actingAs($user);
            $this->getJson('/api/v1/courses')->assertOk()->assertJsonPath('data.0.assignments_count', $count);
            $this->getJson("/api/v1/courses/{$course->id}")->assertOk()->assertJsonPath('data.assignments_count', $count);
        }
        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/courses/{$course->id}/assignments?status=draft")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_assignment_status_filter_rejects_invalid_input_in_standard_api_format(): void
    {
        [, $course] = $this->metadataScenario();
        foreach (['active', ''] as $status) {
            $this->getJson("/api/v1/courses/{$course->id}/assignments?status={$status}")
                ->assertUnprocessable()->assertJsonPath('message', 'Data yang diberikan tidak valid.')->assertJsonValidationErrors('status');
        }
    }

    public static function invalidMetadata(): array
    {
        return [
            'empty title' => ['title', ''],
            'long title' => ['title', str_repeat('a', 256)],
            'non-text title' => ['title', ['invalid']],
            'empty instructions' => ['instructions', ''],
            'invalid instructions' => ['instructions', ['invalid']],
            'invalid date' => ['due_at', 'not-a-date'],
            'impossible date' => ['due_at', '2026-02-30T12:00'],
            'empty date' => ['due_at', ''],
            'zero score' => ['max_score', 0],
            'excess score' => ['max_score', 101],
            'fractional score' => ['max_score', 1.5],
            'invalid late flag' => ['allow_late', 'yes'],
            'null late flag' => ['allow_late', null],
            'invalid status' => ['status', 'active'],
            'empty status' => ['status', ''],
        ];
    }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_metadata_is_rejected_on_api_create_and_partial_update(string $field, mixed $value): void
    {
        [, $course, $assignment] = $this->metadataScenario();
        $this->postJson('/api/v1/assignments', array_replace($this->metadataPayload($course), [$field => $value]))
            ->assertUnprocessable()->assertJsonPath('message', 'Data yang diberikan tidak valid.')->assertJsonValidationErrors($field);
        $this->patchJson("/api/v1/assignments/{$assignment->id}", [$field => $value])
            ->assertUnprocessable()->assertJsonPath('message', 'Data yang diberikan tidak valid.')->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertEquals($assignment->title, $assignment->fresh()->title);
    }

    public function test_api_partial_update_ignores_ownership_fields_and_preserves_submissions(): void
    {
        [$owner, $course, $assignment] = $this->metadataScenario();
        $otherCourse = Course::factory()->create();
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
        $original = $submission->fresh()->getAttributes();
        $this->patchJson("/api/v1/assignments/{$assignment->id}", [
            'status' => 'published', 'course_id' => $otherCourse->id, 'created_by' => $otherCourse->lecturer_id,
        ])->assertOk()->assertJsonPath('data.status', 'published')->assertJsonPath('data.course_id', $course->id);
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'course_id' => $course->id, 'created_by' => $owner->id]);
        $this->assertEquals($original, $submission->fresh()->getAttributes());
        $this->assertEquals($assignment->title, $assignment->fresh()->title);
    }

    public function test_api_create_ignores_creator_and_keeps_default_late_configuration(): void
    {
        [$owner, $course] = $this->metadataScenario();
        $payload = $this->metadataPayload($course);
        unset($payload['allow_late']);
        $this->postJson('/api/v1/assignments', $payload + ['created_by' => User::factory()->create()->id])
            ->assertCreated()->assertJsonPath('data.allow_late', true);
        $this->assertDatabaseHas('assignments', ['title' => $payload['title'], 'created_by' => $owner->id, 'course_id' => $course->id]);
    }

    public function test_students_and_admin_cannot_create_update_delete_or_publish_assignments(): void
    {
        [, $course, $assignment] = $this->metadataScenario();
        foreach (['mahasiswa', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            if ($role === 'mahasiswa') {
                $user->courses()->attach($course, ['enrolled_at' => now()]);
            }
            Sanctum::actingAs($user);
            $this->postJson('/api/v1/assignments', $this->metadataPayload($course))->assertForbidden();
            $this->patchJson("/api/v1/assignments/{$assignment->id}", ['status' => 'published'])->assertForbidden();
            $this->deleteJson("/api/v1/assignments/{$assignment->id}")->assertForbidden();
        }
        $this->assertEquals('draft', $assignment->fresh()->status);
    }

    public function test_api_delete_and_update_require_lecturer_role_even_when_course_owner_id_matches(): void
    {
        [$owner, , $assignment] = $this->metadataScenario();
        $owner->forceFill(['role' => 'mahasiswa'])->save();
        Sanctum::actingAs($owner);
        $this->patchJson("/api/v1/assignments/{$assignment->id}", ['status' => 'published'])->assertForbidden();
        $this->deleteJson("/api/v1/assignments/{$assignment->id}")->assertForbidden();
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'status' => 'draft']);
    }

    public function test_another_lecturer_cannot_publish_or_use_forged_course_id_and_is_authorized_before_validation(): void
    {
        [, , $assignment] = $this->metadataScenario();
        $other = User::factory()->create(['role' => 'dosen']);
        $otherCourse = Course::factory()->create(['lecturer_id' => $other->id]);
        Sanctum::actingAs($other);
        $this->patchJson("/api/v1/assignments/{$assignment->id}", ['status' => 'published', 'course_id' => $otherCourse->id])->assertForbidden();
        $this->patchJson("/api/v1/assignments/{$assignment->id}", ['due_at' => 'invalid'])->assertForbidden();
        $this->assertEquals('draft', $assignment->fresh()->status);
    }

    public function test_assignment_list_filters_status_and_paginates(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        for ($i = 1; $i <= 16; $i++) {
            Assignment::factory()->create([
                'course_id' => $course->id,
                'created_by' => $dosen->id,
                'status' => 'published',
                'due_at' => now()->addDays($i),
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            Assignment::factory()->create([
                'course_id' => $course->id,
                'created_by' => $dosen->id,
                'status' => 'draft',
                'due_at' => now()->addDays(20 + $i),
            ]);
        }

        Sanctum::actingAs($dosen);

        $this->getJson("/api/v1/courses/{$course->id}/assignments?status=draft")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.status', 'draft');

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 18);

        $this->getJson("/api/v1/courses/{$course->id}/assignments?page=2")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_dosen_cannot_list_assignments_from_another_dosens_course(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_mahasiswa_yang_terdaftar_dapat_melihat_daftar_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $mahasiswa->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_mahasiswa_yang_tidak_terdaftar_ditolak_melihat_daftar_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertForbidden();
    }

    public function test_dosen_pemilik_dapat_membuat_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas API Testing',
            'instructions' => 'Menguji pembuatan tugas melalui API.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.course_id', $course->id)
            ->assertJsonPath('data.title', 'Tugas API Testing');

        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'title' => 'Tugas API Testing',
        ]);
    }

    public function test_dosen_tidak_dapat_membuat_tugas_di_course_dosen_lain(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas Tidak Sah',
            'instructions' => 'Pengujian akses.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_dosen_pemilik_dapat_memperbarui_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->putJson("/api/v1/assignments/{$assignment->id}", [
            'title' => 'Judul Tugas Diperbarui',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Tugas Diperbarui');

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Judul Tugas Diperbarui',
        ]);
    }

    public function test_dosen_lain_tidak_dapat_memperbarui_tugas(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->putJson("/api/v1/assignments/{$assignment->id}", [
            'title' => 'Perubahan Tidak Sah',
        ])
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => $assignment->title,
        ]);
    }

    public function test_dosen_pemilik_dapat_menghapus_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->deleteJson("/api/v1/assignments/{$assignment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_dosen_lain_tidak_dapat_menghapus_tugas(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->deleteJson("/api/v1/assignments/{$assignment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
        ]);
    }
}
