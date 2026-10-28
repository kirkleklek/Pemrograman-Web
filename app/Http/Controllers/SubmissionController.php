<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\AssignmentSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny', [
            Submission::class,
            $assignment,
        ]);

        $course = $assignment->course;

        // Pertahankan variabel ini untuk kompatibilitas tes dan view lama.
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

    public function download(Submission $submission): StreamedResponse
    {
        Gate::authorize('download', $submission);

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($submission->file_path),
            404
        );

        return $disk->download(
            $submission->file_path,
            $submission->original_name,
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function store(
        Request $request,
        Assignment $assignment,
        AssignmentSubmissionService $service
    ) {
        Gate::authorize('create', [
            Submission::class,
            $assignment,
        ]);

        $validated = $request->validate([
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

        $user = $request->user();

        $submission = $service->submit(
            $assignment,
            $user,
            $validated['file'],
            $validated['note'] ?? null
        );

        return redirect()
            ->route('mahasiswa.submissions.show', $submission)
            ->with('success', 'Tugas berhasil dikumpulkan.');
    }
}