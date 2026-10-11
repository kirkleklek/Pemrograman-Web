<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiValidationFormatTest extends TestCase
{
    use RefreshDatabase;

    private function assertValidationFormat(
        TestResponse $response,
        string $field
    ): void {
        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data yang diberikan tidak valid.'
            )
            ->assertJsonStructure([
                'message',
                'errors' => [$field],
            ]);
    }

    public function test_login_validation_uses_standard_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bukan-email',
            'password' => 'password',
        ]);

        $this->assertValidationFormat($response, 'email');
    }

    public function test_store_assignment_validation_uses_standard_format(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        Sanctum::actingAs($dosen);

        $response = $this->postJson('/api/v1/assignments', []);

        $this->assertValidationFormat($response, 'course_id');
    }

    public function test_update_assignment_validation_uses_standard_format(): void
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

        $response = $this->putJson(
            "/api/v1/assignments/{$assignment->id}",
            ['due_at' => 'bukan-tanggal']
        );

        $this->assertValidationFormat($response, 'due_at');
    }

    public function test_submission_validation_uses_standard_format(): void
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

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        Sanctum::actingAs($mahasiswa);

        $response = $this->postJson(
            "/api/v1/assignments/{$assignment->id}/submissions",
            ['note' => 'Catatan tanpa berkas']
        );

        $this->assertValidationFormat($response, 'file');
    }

    public function test_grade_validation_uses_standard_format(): void
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

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);

        Sanctum::actingAs($dosen);

        $response = $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            ['score' => 101]
        );

        $this->assertValidationFormat($response, 'score');
    }
}
