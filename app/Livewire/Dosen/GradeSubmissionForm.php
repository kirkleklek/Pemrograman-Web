<?php

namespace App\Livewire\Dosen;

use App\Models\FeedbackTemplate;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GradeSubmissionForm extends Component
{
    #[Locked]
    public Submission $submission;

    public string $score = '';

    public string $feedback = '';

    public $feedbackTemplateId = '';

    public function mount(Submission $submission): void
    {
        $this->submission = $submission;

        $this->submission->load('assignment.course', 'grade');

        $this->authorizeGrading();

        // Muat nilai lama jika submission sudah pernah dinilai.
        if ($this->submission->grade) {
            $this->score = (string) $this->submission->grade->score;
            $this->feedback = $this->submission->grade->feedback ?? '';
        }
    }

    /**
     * Isi feedback otomatis ketika dosen memilih template.
     * Isi tersebut tetap bisa diedit sebelum disimpan.
     */
    public function updatedFeedbackTemplateId($value): void
    {
        $this->authorizeGrading();

        if ($value === '' || $value === null) {
            return;
        }

        $template = FeedbackTemplate::query()
            ->where('lecturer_id', Auth::id())
            ->findOrFail((int) $value);

        $this->feedback = $template->content;
    }

    /**
     * Simpan nilai baru atau perbarui penilaian yang sudah ada.
     */
    public function save(): void
    {
        $this->authorizeGrading();

        $validated = $this->validate([
            'score' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'feedback' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'score.required' => 'Nilai wajib diisi.',
            'score.numeric' => 'Nilai harus berupa angka.',
            'score.min' => 'Nilai tidak boleh kurang dari 0.',
            'score.max' => 'Nilai tidak boleh lebih dari 100.',
            'feedback.string' => 'Feedback harus berupa teks.',
            'feedback.max' => 'Feedback tidak boleh lebih dari 5000 karakter.',
        ]);

        Grade::updateOrCreate(
            [
                'submission_id' => $this->submission->id,
            ],
            [
                'graded_by' => Auth::id(),
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        // Muat kembali relasi agar tampilan menunjukkan nilai terbaru.
        $this->submission->refresh()->load('grade');

        $this->feedbackTemplateId = '';

        session()->flash(
            'success',
            'Nilai dan umpan balik berhasil disimpan.'
        );
    }

    /**
     * Periksa izin kembali pada setiap permintaan Livewire.
     */
    private function authorizeGrading(): void
    {
        $this->submission->loadMissing('assignment.course', 'grade');

        if ($this->submission->grade) {
            Gate::authorize('update', $this->submission->grade);

            return;
        }

        Gate::authorize('create', [
            Grade::class,
            $this->submission,
        ]);
    }

    public function render()
    {
        $this->authorizeGrading();

        $templates = FeedbackTemplate::query()
            ->where('lecturer_id', Auth::id())
            ->orderBy('title')
            ->get();

        return view(
            'livewire.dosen.grade-submission-form',
            compact('templates')
        );
    }
}