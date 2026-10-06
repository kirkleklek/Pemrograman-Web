<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_terdaftar_dapat_mengumpulkan_tugas(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $course->students()->attach($mahasiswa->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $file = UploadedFile::fake()->create(
            'tugas.pdf',
            500,
            'application/pdf'
        );

        $response = $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            [
                'file' => $file,
                'note' => 'Tugas sudah selesai.',
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.assignment_id',
                $assignment->id
            )
            ->assertJsonPath(
                'data.user_id',
                $mahasiswa->id
            )
            ->assertJsonPath(
                'data.original_name',
                'tugas.pdf'
            )
            ->assertJsonPath(
                'data.note',
                'Tugas sudah selesai.'
            );

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'original_name' => 'tugas.pdf',
        ]);

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        Storage::disk('local')->assertExists($submission->file_path);
    }

    public function test_mahasiswa_yang_tidak_terdaftar_ditolak_mengumpulkan_tugas(): void
    {
        Storage::fake('local');

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
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $file = UploadedFile::fake()->create(
            'tugas.pdf',
            500,
            'application/pdf'
        );

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            [
                'file' => $file,
                'note' => 'Tidak seharusnya diterima.',
            ],
            [
                'Accept' => 'application/json',
            ]
        )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);
    }
}