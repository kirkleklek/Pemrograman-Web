<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubmissionController extends Controller
{
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny', [Submission::class, $assignment]);

        $course = $assignment->course;

        $submissions = $assignment->submissions()
            ->with(['student', 'grade'])
            ->latest('submitted_at')
            ->get();

        return view('submissions.index', compact(
            'assignment',
            'course',
            'submissions'
        ));
    }

    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $submission->load([
            'assignment.course',
            'student',
            'grade',
        ]);

        $course = $submission->assignment->course;

        return view('submissions.show', compact(
            'submission',
            'course'
        ));
    }

    public function store(Request $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        $user = auth()->user();

        $existingSubmission = $assignment->submissions()
            ->where('user_id', $user->id)
            ->first();

        abort_unless($existingSubmission === null, 403);

        $validated = $request->validate([
            'file' => ['required', 'file'],
            'note' => ['nullable', 'string'],
        ]);

        $isLate = now()->greaterThan($assignment->due_at);

        abort_unless(
            ! $isLate || $assignment->allow_late,
            403
        );

        $file = $request->file('file');

        $submission = $assignment->submissions()->create([
            'user_id' => $user->id,
            'file_path' => $file->store('submissions'),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'note' => $validated['note'] ?? null,
            'submitted_at' => now(),
            'is_late' => $isLate,
        ]);

        return redirect()
            ->route('mahasiswa.submissions.show', $submission)
            ->with('success', 'Tugas berhasil dikumpulkan.');
    }
}