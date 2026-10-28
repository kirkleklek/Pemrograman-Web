<?php

namespace App\Livewire\Mahasiswa;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Services\AssignmentSubmissionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmissionForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public Assignment $assignment;

    public $file = null;

    public ?string $note = null;

    public function mount(Assignment $assignment): void
    {
        Gate::authorize('create', [
            Submission::class,
            $assignment,
        ]);

        $this->assignment = $assignment;
    }

    public function submit(
        AssignmentSubmissionService $service
    ) {
        $validated = $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120',
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $student = auth()->user();

        abort_unless($student instanceof User, 403);

        Gate::authorize('create', [
            Submission::class,
            $this->assignment,
        ]);

        $submission = $service->submit(
            $this->assignment,
            $student,
            $validated['file'],
            $validated['note'] ?? null,
        );

        $this->reset(['file', 'note']);

        session()->flash(
            'success',
            'Tugas berhasil dikumpulkan.'
        );

        return redirect()->route(
            'mahasiswa.submissions.show',
            $submission
        );
    }

    public function render()
    {
        $existingSubmission = $this->assignment
            ->submissions()
            ->where('user_id', auth()->id())
            ->first();

        return view('livewire.mahasiswa.submission-form', [
            'existingSubmission' => $existingSubmission,
        ]);
    }
}
