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

    public function test_dosen_yang_bukan_pemilik_course_ditolak_memberi_nilai(): void
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
                'score' => 80,
                'feedback' => 'Tidak seharusnya bisa.',
            ]
        )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }
}