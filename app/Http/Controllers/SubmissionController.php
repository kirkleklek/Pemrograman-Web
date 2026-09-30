<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Assignment $assignment)
    {
        $course = $assignment->course;

        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

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
        $user = auth()->user();

        $submission->load([
            'assignment.course',
            'student',
            'grade',
        ]);

        $course = $submission->assignment->course;

        if ($user->role === 'dosen') {
            abort_unless(
                $course->lecturer_id === $user->id,
                403
            );
        } else {
            abort_unless(
                $submission->user_id === $user->id
                    && $user->courses()->whereKey($course->id)->exists(),
                403
            );
        }

        return view('submissions.show', compact(
            'submission',
            'course'
        ));
    }

    public function store(Request $request, Assignment $assignment)
    {
        $user = auth()->user();
        $course = $assignment->course;

        abort_unless(
            $user->role === 'mahasiswa'
                && $user->courses()->whereKey($course->id)->exists(),
            403
        );

        abort_unless(
            $assignment->status === 'published',
            403
        );

        $existingSubmission = $assignment->submissions()
            ->where('user_id', $user->id)
            ->first();

        abort_unless(
            $existingSubmission === null,
            403
        );

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