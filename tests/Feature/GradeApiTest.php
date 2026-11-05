<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GradeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dapat_memberi_nilai_dan_melakukan_penilaian_ulang(): void
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
            [
                'score' => 85,
                'feedback' => 'Sudah baik.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.submission_id', $submission->id)
            ->assertJsonPath('data.score', '85.00')
            ->assertJsonPath('data.feedback', 'Sudah baik.');

        $gradeId = $response->json('data.id');

        $response = $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            [
                'score' => 90,
                'feedback' => 'Sudah diperbaiki.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $gradeId)
            ->assertJsonPath('data.score', '90.00')
            ->assertJsonPath('data.feedback', 'Sudah diperbaiki.');
    }

    public function test_dosen_yang_bukan_pemilik_course_ditolak_memberi_nilai_sebelum_validasi(): void
    {
        $dosenPemilik = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosenPemilik->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosenPemilik->id,
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);

        Sanctum::actingAs($dosenLain);

        $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            [
                'score' => 101,
                'feedback' => str_repeat('a', 5001),
            ]
        )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_mahasiswa_tidak_dapat_memberi_nilai(): void
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

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            [
                'score' => 85,
                'feedback' => 'Percobaan memberi nilai oleh mahasiswa.',
            ]
        )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
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

        foreach ([-1, 101] as $score) {
            $this->putJson(
                "/api/v1/submissions/{$submission->id}/grade",
                ['score' => $score]
            )
                ->assertUnprocessable()
                ->assertJsonPath(
                    'message',
                    'Data yang diberikan tidak valid.'
                )
                ->assertJsonValidationErrors(['score']);
        }

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_nilai_tidak_boleh_melebihi_max_score_dan_batas_maksimum_diterima(): void
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
            'max_score' => 50,
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);

        Sanctum::actingAs($dosen);

        // Nilai 51 harus ditolak karena batasnya 50.
        $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            ['score' => 51]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);

        // Nilai tepat 50 harus diterima.
        $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            ['score' => 50]
        )
            ->assertCreated()
            ->assertJsonPath('data.score', '50.00');

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 50,
        ]);
    }

    public function test_feedback_lebih_dari_5000_karakter_ditolak(): void
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

        $this->putJson(
            "/api/v1/submissions/{$submission->id}/grade",
            [
                'score' => 80,
                'feedback' => str_repeat('a', 5001),
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Data yang diberikan tidak valid.'
            )
            ->assertJsonValidationErrors(['feedback']);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }
}