<?php

namespace Tests\Feature;

use App\Livewire\Dosen\GradeSubmissionForm;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\FeedbackTemplate;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GradeSubmissionFormTest extends TestCase
{
    use RefreshDatabase;

    private function createSubmissionFor(User $dosen): Submission
    {
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

        return Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
        ]);
    }

    public function test_dosen_dapat_memberikan_nilai_menggunakan_livewire(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        Livewire::actingAs($dosen)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->set('score', '86')
            ->set('feedback', 'Jawaban sudah baik.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('grades', 1);

        $grade = Grade::where(
            'submission_id',
            $submission->id
        )->firstOrFail();

        $this->assertSame($dosen->id, $grade->graded_by);
        $this->assertSame('86.00', $grade->score);
        $this->assertSame('Jawaban sudah baik.', $grade->feedback);
        $this->assertNotNull($grade->graded_at);
    }

    public function test_penilaian_ulang_memperbarui_nilai_yang_sama(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        $grade = Grade::create([
            'submission_id' => $submission->id,
            'graded_by' => $dosen->id,
            'score' => 70,
            'feedback' => 'Perlu perbaikan.',
            'graded_at' => now()->subHour(),
        ]);

        Livewire::actingAs($dosen)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->assertSet('score', '70.00')
            ->assertSet('feedback', 'Perlu perbaikan.')
            ->set('score', '92')
            ->set('feedback', 'Revisi sudah sangat baik.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('grades', 1);

        $updatedGrade = Grade::where(
            'submission_id',
            $submission->id
        )->firstOrFail();

        $this->assertSame($grade->id, $updatedGrade->id);
        $this->assertSame('92.00', $updatedGrade->score);
        $this->assertSame(
            'Revisi sudah sangat baik.',
            $updatedGrade->feedback
        );
    }

    public function test_memilih_template_milik_dosen_mengisi_feedback(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $dosen->id,
            'title' => 'Jawaban Baik',
            'content' => 'Jawaban sudah sesuai dengan materi.',
        ]);

        Livewire::actingAs($dosen)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->set('feedbackTemplateId', (string) $template->id)
            ->assertSet(
                'feedback',
                'Jawaban sudah sesuai dengan materi.'
            );
    }

    public function test_feedback_template_dapat_diedit_sebelum_disimpan(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $dosen->id,
            'title' => 'Template Revisi',
            'content' => 'Jawaban sudah baik.',
        ]);

        Livewire::actingAs($dosen)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->set('feedbackTemplateId', (string) $template->id)
            ->set('feedback', 'Jawaban sudah baik dan lebih terperinci.')
            ->set('score', '88')
            ->call('save')
            ->assertHasNoErrors();

        $grade = Grade::where(
            'submission_id',
            $submission->id
        )->firstOrFail();

        $this->assertSame(
            'Jawaban sudah baik dan lebih terperinci.',
            $grade->feedback
        );

        // Template asli tidak ikut berubah.
        $this->assertDatabaseHas('feedback_templates', [
            'id' => $template->id,
            'content' => 'Jawaban sudah baik.',
        ]);
    }

    public function test_dosen_tidak_dapat_memilih_template_dosen_lain(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        $templateLain = FeedbackTemplate::create([
            'lecturer_id' => $dosenLain->id,
            'title' => 'Template Privat',
            'content' => 'Isi template dosen lain.',
        ]);

        try {
            Livewire::actingAs($dosen)
                ->test(GradeSubmissionForm::class, [
                    'submission' => $submission,
                ])
                ->set(
                    'feedbackTemplateId',
                    (string) $templateLain->id
                );

            $this->fail(
                'Template milik dosen lain seharusnya ditolak.'
            );
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(
                FeedbackTemplate::class,
                $exception->getModel()
            );
        }

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_dosen_lain_tidak_dapat_memberikan_nilai(): void
    {
        $pemilik = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($pemilik);

        Livewire::actingAs($dosenLain)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        foreach ([-1, 101] as $score) {
            Livewire::actingAs($dosen)
                ->test(GradeSubmissionForm::class, [
                    'submission' => $submission,
                ])
                ->set('score', (string) $score)
                ->set('feedback', 'Feedback pengujian.')
                ->call('save')
                ->assertHasErrors(['score']);
        }

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }

    public function test_feedback_lebih_dari_5000_karakter_ditolak(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submission = $this->createSubmissionFor($dosen);

        Livewire::actingAs($dosen)
            ->test(GradeSubmissionForm::class, [
                'submission' => $submission,
            ])
            ->set('score', '80')
            ->set('feedback', str_repeat('a', 5001))
            ->call('save')
            ->assertHasErrors([
                'feedback' => 'max',
            ]);

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $submission->id,
        ]);
    }
}
