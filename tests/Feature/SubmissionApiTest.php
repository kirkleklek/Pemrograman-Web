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
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $response = $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            [
                'file' => UploadedFile::fake()->create('tugas.pdf', 500, 'application/pdf'),
                'note' => 'Tugas sudah selesai.',
            ],
            ['Accept' => 'application/json']
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.assignment_id', $assignment->id)
            ->assertJsonPath('data.user_id', $mahasiswa->id)
            ->assertJsonPath('data.original_name', 'tugas.pdf')
            ->assertJsonPath('data.note', 'Tugas sudah selesai.')
            ->assertJsonMissingPath('data.file_path');

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'original_name' => 'tugas.pdf',
        ]);

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing($submission->file_path);

        $this->actingAs($mahasiswa, 'web')
            ->get(route('submissions.download', $submission))
            ->assertOk()
            ->assertDownload('tugas.pdf');
    }

    public function test_mahasiswa_yang_tidak_terdaftar_ditolak_mengumpulkan_tugas(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            [
                'file' => UploadedFile::fake()->create('tugas.pdf', 500, 'application/pdf'),
                'note' => 'Tidak seharusnya diterima.',
            ],
            ['Accept' => 'application/json']
        )->assertForbidden()->assertJson([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ]);

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);
    }

    public function test_dosen_pemilik_dapat_melihat_daftar_submission(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->getJson("/api/v1/assignments/{$assignment->id}/submissions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $submission->id)
            ->assertJsonPath('data.0.student.id', $mahasiswa->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.file_path');
    }

    public function test_dosen_lain_tidak_dapat_melihat_daftar_submission(): void
    {
        $pemilik = User::factory()->create(['role' => 'dosen']);
        $dosenLain = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $pemilik->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $pemilik->id,
        ]);

        Sanctum::actingAs($dosenLain);

        $this->getJson("/api/v1/assignments/{$assignment->id}/submissions")
            ->assertForbidden()
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_mahasiswa_tidak_dapat_melihat_daftar_submission(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/assignments/{$assignment->id}/submissions")
            ->assertForbidden()
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_mahasiswa_dapat_mengumpulkan_ulang_tugas_melalui_api(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        $oldPath = 'submissions/versi-lama.pdf';
        Storage::disk('local')->put($oldPath, 'Berkas versi lama');
        $oldSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'file_path' => $oldPath,
            'original_name' => 'tugas-awal.pdf',
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            [
                'file' => UploadedFile::fake()->create('tugas-revisi.pdf', 500, 'application/pdf'),
                'note' => 'Ini versi revisi.',
            ],
            ['Accept' => 'application/json']
        )
            ->assertOk()
            ->assertJsonPath('data.id', $oldSubmission->id)
            ->assertJsonPath('data.original_name', 'tugas-revisi.pdf')
            ->assertJsonPath('data.note', 'Ini versi revisi.')
            ->assertJsonMissingPath('data.file_path');

        $this->assertDatabaseCount('submissions', 1);

        $updatedSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertSame($oldSubmission->id, $updatedSubmission->id);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($updatedSubmission->file_path);
    }

    public function test_mahasiswa_ditolak_mengumpulkan_tugas_yang_belum_dipublikasikan(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'draft',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            ['file' => UploadedFile::fake()->create('tugas.pdf', 500, 'application/pdf')],
            ['Accept' => 'application/json']
        )->assertForbidden()->assertJson([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ]);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pengumpulan_tugas_terlambat_ditolak_jika_allow_late_false(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            ['file' => UploadedFile::fake()->create('tugas-terlambat.pdf', 500, 'application/pdf')],
            ['Accept' => 'application/json']
        )->assertForbidden()->assertJson([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ]);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_file_dengan_format_tidak_diizinkan_ditolak(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            ['file' => UploadedFile::fake()->create('script.pdf', 1, 'text/x-php')],
            ['Accept' => 'application/json']
        )->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_file_lebih_dari_5_mb_ditolak(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->post(
            "/api/v1/assignments/{$assignment->id}/submissions",
            ['file' => UploadedFile::fake()->create('terlalu-besar.pdf', 5121, 'application/pdf')],
            ['Accept' => 'application/json']
        )->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('submissions', 0);
    }
}