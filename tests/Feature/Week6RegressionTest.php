<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\IntegrationFixture;
use Tests\TestCase;

// Contract sources: docs/api.md, docs/minggu-06-Eagan.md, routes/api.php,
// API Resources/Form Requests, and the existing *ApiTest classes.
class Week6RegressionTest extends TestCase
{
    use IntegrationFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createIntegrationFixture();
    }

    public function test_real_bearer_login_me_logout_and_revoked_token_contract(): void
    {
        $credential = bin2hex(random_bytes(16));
        $user = User::factory()->create(['password' => $credential]);
        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => $credential])
            ->assertOk()->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role', 'nim_nip', 'email_verified_at']]])
            ->assertJsonMissingPath('data.user.password')->assertJsonMissingPath('data.user.remember_token');
        $token = $login->json('data.token');
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        // Separate HTTP requests do not reuse Laravel's in-process resolved guard.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken('invalid-audit-token')->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_login_failure_is_generic_and_sixth_attempt_is_rate_limited(): void
    {
        $wrongPassword = bin2hex(random_bytes(16));
        $known = $this->postJson('/api/v1/auth/login', ['email' => $this->students[0]->email, 'password' => $wrongPassword])->assertUnprocessable();
        $unknown = $this->postJson('/api/v1/auth/login', ['email' => 'unknown@audit.example.test', 'password' => $wrongPassword])->assertUnprocessable();
        $this->assertSame($known->json(), $unknown->json());
        $known->assertJsonStructure(['message', 'errors' => ['email']]);
        for ($attempt = 3; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => $this->students[0]->email, 'password' => $wrongPassword])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/login', ['email' => $this->students[0]->email, 'password' => $wrongPassword])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_api_sixty_request_rate_limit_is_preserved(): void
    {
        Sanctum::actingAs($this->students[0]);
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/v1/me')->assertOk();
        }
        $this->getJson('/api/v1/me')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_assignment_submission_grade_and_delete_lifecycle(): void
    {
        Sanctum::actingAs($this->lecturers[0]);
        $created = $this->postJson('/api/v1/assignments', $this->assignmentPayload($this->courses[0]))
            ->assertCreated()->assertJsonStructure(['data' => ['id', 'course_id', 'title', 'instructions', 'due_at', 'max_score', 'allow_late', 'status']]);
        $id = $created->json('data.id');
        $this->assertDatabaseHas('assignments', ['id' => $id, 'created_by' => $this->lecturers[0]->id, 'course_id' => $this->courses[0]->id]);
        $this->putJson('/api/v1/assignments/'.$id, ['title' => 'Tugas direvisi'])->assertOk()->assertJsonPath('data.title', 'Tugas direvisi');
        $this->patchJson('/api/v1/assignments/'.$id, ['allow_late' => true])->assertOk()->assertJsonPath('data.allow_late', true);
        Sanctum::actingAs($this->students[0]);
        $submitted = $this->post('/api/v1/assignments/'.$id.'/submissions', [
            'file' => UploadedFile::fake()->create('regresi.pdf', 10, 'application/pdf'), 'note' => 'Jawaban regresi',
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.assignment_id', $id)->assertJsonPath('data.user_id', $this->students[0]->id)
            ->assertJsonPath('data.is_late', false)->assertJsonMissingPath('data.student.password');
        $submissionId = $submitted->json('data.id');
        $this->assertDatabaseHas('submissions', ['id' => $submissionId, 'assignment_id' => $id, 'note' => 'Jawaban regresi']);
        Storage::disk('local')->assertExists($submitted->json('data.file_path'));
        Sanctum::actingAs($this->lecturers[0]);
        $this->getJson('/api/v1/assignments/'.$id.'/submissions')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $submissionId)->assertJsonPath('meta.total', 1);
        $grade = $this->putJson('/api/v1/submissions/'.$submissionId.'/grade', ['score' => 80, 'feedback' => 'Baik'])
            ->assertCreated()->assertJsonPath('data.score', '80.00')->assertJsonMissingPath('data.grader.password');
        $this->putJson('/api/v1/submissions/'.$submissionId.'/grade', ['score' => 90, 'feedback' => 'Revisi'])
            ->assertOk()->assertJsonPath('data.id', $grade->json('data.id'))->assertJsonPath('data.score', '90.00');
        $this->assertSame(1, Grade::where('submission_id', $submissionId)->count());
        $this->assertDatabaseHas('grades', ['submission_id' => $submissionId, 'graded_by' => $this->lecturers[0]->id, 'score' => 90]);
        $this->deleteJson('/api/v1/assignments/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('assignments', ['id' => $id]);
        $this->assertDatabaseMissing('submissions', ['id' => $submissionId]);
        $this->assertDatabaseMissing('grades', ['submission_id' => $submissionId]);
    }

    public function test_material_pagination_and_user_resource_shape_remain_scoped(): void
    {
        for ($index = 1; $index <= 15; $index++) {
            Material::create([
                'course_id' => $this->courses[0]->id, 'uploaded_by' => $this->lecturers[0]->id,
                'title' => 'Materi pagination '.$index, 'type' => 'link', 'external_url' => 'https://example.test/'.$index,
            ]);
        }
        Sanctum::actingAs($this->students[0]);
        $first = $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/materials')->assertOk()
            ->assertJsonCount(15, 'data')->assertJsonPath('meta', ['current_page' => 1, 'last_page' => 2, 'total' => 16]);
        $second = $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/materials?page=2')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta', ['current_page' => 2, 'last_page' => 2, 'total' => 16])
            ->assertJsonStructure(['data' => [['id', 'course_id', 'title', 'description', 'type', 'file_path', 'original_name',
                'file_size', 'mime_type', 'external_url', 'uploader' => ['id', 'name', 'email', 'role', 'nim_nip', 'email_verified_at']]]]);
        $this->assertNotContains($second->json('data.0.id'), array_column($first->json('data'), 'id'));
        foreach ([$first, $second] as $response) {
            $response->assertDontSee($this->materials[1]->title, false)
                ->assertJsonMissingPath('data.0.uploader.password')->assertJsonMissingPath('data.0.uploader.remember_token');
        }
    }

    public function test_draft_filter_and_counts_do_not_disclose_unpublished_assignments(): void
    {
        $draft = $this->fixtureAssignment($this->courses[0], ['status' => 'draft', 'title' => 'Draft rahasia']);
        Sanctum::actingAs($this->students[0]);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id)->assertOk()->assertJsonPath('data.assignments_count', 1);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/assignments?status=draft')
            ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0)->assertDontSee($draft->title, false);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/assignments?status=published')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->assignments[0]->id);
        Sanctum::actingAs($this->lecturers[0]);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id)->assertOk()->assertJsonPath('data.assignments_count', 2);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/assignments?status=draft')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $draft->id);
    }

    public function test_invalid_requests_keep_standard_errors_and_do_not_write(): void
    {
        Sanctum::actingAs($this->lecturers[0]);
        $before = $this->databaseSnapshot();
        $cases = [
            ['POST', '/api/v1/assignments', array_replace($this->assignmentPayload($this->courses[0]), ['course_id' => 999999999]), 'course_id'],
            ['POST', '/api/v1/assignments', array_replace($this->assignmentPayload($this->courses[0]), ['max_score' => 0]), 'max_score'],
            ['PUT', '/api/v1/assignments/'.$this->assignments[0]->id, ['due_at' => 'invalid'], 'due_at'],
            ['PUT', '/api/v1/submissions/'.$this->submissions[0]->id.'/grade', ['score' => 101], 'score'],
            ['GET', '/api/v1/courses/'.$this->courses[0]->id.'/assignments?status=invalid', [], 'status'],
        ];
        foreach ($cases as [$method, $path, $data, $field]) {
            $this->json($method, $path, $data)->assertUnprocessable()
                ->assertJsonPath('message', 'Data yang diberikan tidak valid.')
                ->assertJsonStructure(['message', 'errors' => [$field]]);
            $this->assertSame($before, $this->databaseSnapshot());
        }
    }

    public function test_rejected_submission_states_do_not_store_rows_or_files(): void
    {
        $draft = $this->fixtureAssignment($this->courses[0], ['status' => 'draft']);
        $expired = $this->fixtureAssignment($this->courses[0], ['due_at' => now()->subDay(), 'allow_late' => false]);
        Sanctum::actingAs($this->students[0]);
        $before = $this->databaseSnapshot();
        $filesBefore = Storage::disk('local')->allFiles();
        foreach ([$draft, $expired, $this->assignments[0]] as $assignment) {
            $this->post('/api/v1/assignments/'.$assignment->id.'/submissions', [
                'file' => UploadedFile::fake()->create('ditolak.pdf', 10),
            ], ['Accept' => 'application/json'])->assertForbidden();
            $this->assertSame($before, $this->databaseSnapshot());
            $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        }
        $this->postJson('/api/v1/assignments/'.$draft->id.'/submissions', ['note' => 'Tanpa file'])
            ->assertUnprocessable()->assertJsonStructure(['message', 'errors' => ['file']]);
        $this->assertSame($before, $this->databaseSnapshot());
        $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
    }
}
