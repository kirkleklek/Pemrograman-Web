<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\SubmissionForm;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SubmissionFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Storage::fake('local');
    }

    private function createEnrolledStudentAssignment(
        array $overrides = []
    ): array {
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

        $assignment = Assignment::factory()->create(array_merge([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => false,
        ], $overrides));

        return [$mahasiswa, $assignment];
    }

    public function test_mahasiswa_dapat_mengumpulkan_tugas_pdf(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas.pdf',
                500,
                'application/pdf'
            ))
            ->set('note', 'Tugas sudah selesai.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertDatabaseCount('submissions', 1);

        $this->assertSame('tugas.pdf', $submission->original_name);
        $this->assertSame('Tugas sudah selesai.', $submission->note);
        $this->assertFalse($submission->is_late);

        Storage::disk($disk)->assertExists($submission->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('submissions.download', $submission))
            ->assertOk()->assertDownload('tugas.pdf');
    }

    public function test_mahasiswa_dapat_mengumpulkan_ulang_tugas(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $oldPath = 'submissions/berkas-lama.pdf';

        Storage::disk($disk)->put($oldPath, 'berkas lama');

        $original = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'file_path' => $oldPath,
            'original_name' => 'berkas-lama.pdf',
            'file_size' => 11,
            'submitted_at' => now()->subHour(),
            'is_late' => false,
        ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-revisi.pdf',
                600,
                'application/pdf'
            ))
            ->set('note', 'Versi revisi.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $updated = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertSame($original->id, $updated->id);
        $this->assertSame('tugas-revisi.pdf', $updated->original_name);
        $this->assertSame('Versi revisi.', $updated->note);

        $this->assertDatabaseCount('submissions', 1);

        Storage::disk($disk)->assertExists($updated->file_path);
        Storage::disk($disk)->assertMissing($oldPath);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('submissions.download', $updated))
            ->assertOk()->assertDownload('tugas-revisi.pdf');
    }

    public function test_berkas_dengan_tipe_tidak_valid_ditolak(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'script.txt',
                100,
                'text/plain'
            ))
            ->call('submit')
            ->assertHasErrors(['file' => 'mimes']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_berkas_lebih_dari_5_mb_ditolak(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-besar.pdf',
                5121,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasErrors(['file' => 'max']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pengumpulan_terlambat_ditolak_jika_tidak_diizinkan(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment([
                'due_at' => now()->subMinute(),
                'allow_late' => false,
            ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-terlambat.pdf',
                500,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasErrors(['file']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pengumpulan_terlambat_diterima_jika_diizinkan(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment([
                'due_at' => now()->subMinute(),
                'allow_late' => true,
            ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-terlambat.pdf',
                500,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertTrue($submission->is_late);
        Storage::disk($disk)->assertExists($submission->file_path);
    }
}
