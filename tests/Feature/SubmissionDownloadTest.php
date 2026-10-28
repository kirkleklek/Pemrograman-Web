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

class SubmissionDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $lecturer;

    private Course $course;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $this->student = User::factory()->create(['role' => 'mahasiswa']);
        $this->lecturer = User::factory()->create(['role' => 'dosen']);
        $this->course = Course::factory()->create(['lecturer_id' => $this->lecturer->id]);
        $this->course->students()->attach($this->student, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->lecturer->id,
            'status' => 'published',
        ]);
        $this->submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'original_name' => 'jawaban.pdf',
        ]);
        Storage::disk('local')->put($this->submission->file_path, 'Isi submission privat');
    }

    public function test_owner_can_download_private_file_with_original_name(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))
            ->assertOk()
            ->assertDownload('jawaban.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame('Isi submission privat', $response->streamedContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        Storage::disk('public')->assertMissing($this->submission->file_path);
    }

    public function test_owner_cannot_download_after_assignment_becomes_draft_but_can_after_republication(): void
    {
        $url = route('submissions.download', $this->submission);

        $this->actingAs($this->student)->get($url)
            ->assertOk()->assertStreamedContent('Isi submission privat');

        $this->submission->assignment->update(['status' => 'draft']);

        $this->get($url)->assertForbidden()
            ->assertHeaderMissing('Content-Disposition')
            ->assertDontSee('Isi submission privat');

        $this->submission->assignment->update(['status' => 'published']);

        $this->get($url)->assertOk()->assertDownload('jawaban.pdf')
            ->assertStreamedContent('Isi submission privat');
    }

    public function test_course_lecturer_and_admin_keep_download_access_after_assignment_becomes_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['published', 'draft'] as $status) {
            $this->submission->assignment->update(['status' => $status]);

            foreach ([$this->lecturer, $admin] as $user) {
                $this->actingAs($user)
                    ->get(route('submissions.download', $this->submission))
                    ->assertOk()->assertDownload('jawaban.pdf')
                    ->assertStreamedContent('Isi submission privat');
            }
        }

        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $this->actingAs($otherLecturer)
            ->get(route('submissions.download', $this->submission))->assertForbidden();
    }

    public function test_student_cannot_swap_published_submission_id_for_another_students_draft_submission(): void
    {
        $other = User::factory()->create(['role' => 'mahasiswa']);
        $this->course->students()->attach($other, ['enrolled_at' => now()]);
        $draftAssignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->lecturer->id,
            'status' => 'draft',
        ]);
        $foreign = Submission::factory()->create([
            'assignment_id' => $draftAssignment->id,
            'user_id' => $other->id,
        ]);
        Storage::disk('local')->put($foreign->file_path, 'Submission draft mahasiswa lain');

        $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))->assertOk();

        $this->get(route('submissions.download', $foreign))
            ->assertForbidden()->assertHeaderMissing('Content-Disposition')
            ->assertDontSee('Submission draft mahasiswa lain');
    }

    public function test_course_lecturer_can_download(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('submissions.download', $this->submission))
            ->assertOk()->assertDownload('jawaban.pdf')
            ->assertStreamedContent('Isi submission privat');
    }

    public function test_admin_can_download_without_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('submissions.download', $this->submission))
            ->assertOk()->assertDownload('jawaban.pdf')
            ->assertStreamedContent('Isi submission privat');
    }

    public function test_enrolled_student_cannot_manipulate_submission_id_to_download_peers_file(): void
    {
        $other = User::factory()->create(['role' => 'mahasiswa']);
        $this->course->students()->attach($other, ['enrolled_at' => now()]);
        $ownSubmission = Submission::factory()->create([
            'assignment_id' => $this->submission->assignment_id,
            'user_id' => $other->id,
        ]);
        Storage::disk('local')->put($ownSubmission->file_path, 'Jawaban sendiri');

        $this->actingAs($other)
            ->get(route('submissions.download', $ownSubmission))
            ->assertOk()->assertStreamedContent('Jawaban sendiri');

        $this->get(route('submissions.download', $this->submission))
            ->assertForbidden()
            ->assertHeaderMissing('Content-Disposition')
            ->assertDontSee('Isi submission privat');
    }

    public function test_lecturer_cannot_manipulate_submission_id_to_download_another_courses_file(): void
    {
        $foreignSubmission = Submission::factory()->create();
        Storage::disk('local')->put($foreignSubmission->file_path, 'Submission mata kuliah lain');

        $this->actingAs($this->lecturer)
            ->get(route('submissions.download', $this->submission))->assertOk();

        $this->get(route('submissions.download', $foreignSubmission))
            ->assertForbidden()
            ->assertHeaderMissing('Content-Disposition')
            ->assertDontSee('Submission mata kuliah lain');
    }

    public function test_assignment_creator_without_course_ownership_cannot_download(): void
    {
        $creator = User::factory()->create(['role' => 'dosen']);
        $this->submission->assignment->update(['created_by' => $creator->id]);

        $this->actingAs($creator)
            ->get(route('submissions.download', $this->submission))->assertForbidden();
    }

    public function test_owner_loses_download_access_after_unenrollment(): void
    {
        $this->course->students()->detach($this->student);

        $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))->assertForbidden();
    }

    public function test_guest_must_login_even_with_a_valid_submission_id(): void
    {
        $this->get(route('submissions.download', $this->submission))
            ->assertRedirect(route('login'))
            ->assertHeaderMissing('Content-Disposition');

        $this->getJson(route('submissions.download', $this->submission))->assertUnauthorized();
    }

    public function test_missing_submission_returns_not_found(): void
    {
        $this->actingAs($this->student)
            ->get(route('submissions.download', 999999))->assertNotFound();
    }

    public function test_missing_private_file_returns_not_found_without_public_fallback(): void
    {
        Storage::disk('local')->delete($this->submission->file_path);
        Storage::disk('public')->put($this->submission->file_path, 'Salinan publik');

        $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))
            ->assertNotFound()->assertDontSee('Salinan publik');
    }

    public function test_authorization_precedes_file_existence_check(): void
    {
        Storage::disk('local')->delete($this->submission->file_path);
        $other = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($other)
            ->get(route('submissions.download', $this->submission))->assertForbidden();
    }

    public function test_private_file_is_not_available_through_public_storage_url(): void
    {
        $this->get('/storage/'.$this->submission->file_path)
            ->assertNotFound()->assertDontSee('Isi submission privat');
        Storage::disk('public')->assertMissing($this->submission->file_path);
    }

    public function test_blade_links_use_authorized_download_route(): void
    {
        $url = route('submissions.download', $this->submission);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.submissions.show', $this->submission))
            ->assertOk()->assertSee('href="'.$url.'"', false)
            ->assertDontSee('/storage/'.$this->submission->file_path);

        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->submission->assignment))
            ->assertOk()->assertSee('href="'.$url.'"', false);
    }

    public function test_livewire_download_link_rechecks_permission_after_enrollment_is_revoked(): void
    {
        $url = route('submissions.download', $this->submission);

        Livewire::actingAs($this->student)
            ->test(SubmissionForm::class, ['assignment' => $this->submission->assignment])
            ->assertSeeHtml('href="'.$url.'"');

        $this->course->students()->detach($this->student);

        $this->get($url)->assertForbidden();
    }

    public function test_web_upload_and_resubmission_stay_private_when_default_disk_is_public(): void
    {
        $this->submission->delete();
        Storage::disk('local')->delete($this->submission->file_path);
        $url = route('mahasiswa.assignments.submissions.store', $this->submission->assignment);

        $this->actingAs($this->student)->post($url, [
            'file' => UploadedFile::fake()->createWithContent('awal.pdf', "%PDF-1.4\nJawaban awal"),
        ])->assertRedirect();

        $original = Submission::sole();
        Storage::disk('local')->assertExists($original->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->post($url, [
            'file' => UploadedFile::fake()->createWithContent('revisi.pdf', "%PDF-1.4\nJawaban revisi"),
        ])->assertRedirect();

        $updated = Submission::sole();
        $this->assertSame($original->id, $updated->id);
        Storage::disk('local')->assertMissing($original->file_path);
        Storage::disk('local')->assertExists($updated->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('submissions.download', $updated))
            ->assertOk()->assertDownload('revisi.pdf')
            ->assertStreamedContent("%PDF-1.4\nJawaban revisi");
    }
}
