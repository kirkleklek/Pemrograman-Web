<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        $course = $assignment->course;

        abort_unless(
            $user->role === 'dosen'
                && $course->lecturer_id === $user->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $submissions = $assignment->submissions()
            ->with('student')
            ->latest('submitted_at')
            ->paginate(15);

        return SubmissionResource::collection(
            $submissions->getCollection()
        )->additional([
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    public function store(
        StoreSubmissionRequest $request,
        Assignment $assignment
    ) {
        $user = $request->user();

        $course = $assignment->course;

        abort_unless(
            $user->role === 'mahasiswa'
                && $user->courses()
                    ->whereKey($course->id)
                    ->exists(),
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        abort_unless(
            $assignment->status === 'published',
            403,
            'Tugas belum dapat dikumpulkan.'
        );

        $existingSubmission = $assignment->submissions()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless(
            ! $existingSubmission,
            403,
            'Anda sudah mengumpulkan tugas ini.'
        );

        $isLate = now()->greaterThan($assignment->due_at);

        abort_unless(
            ! $isLate || $assignment->allow_late,
            403,
            'Batas waktu pengumpulan telah berakhir.'
        );

        $file = $request->file('file');

        $submission = $assignment->submissions()->create([
            'user_id' => $user->id,
            'file_path' => $file->store('submissions', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'note' => $request->validated()['note'] ?? null,
            'submitted_at' => now(),
            'is_late' => $isLate,
        ]);

        $submission->load('student');

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }
}