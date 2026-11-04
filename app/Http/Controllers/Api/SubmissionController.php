<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\AssignmentSubmissionService;
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
        Assignment $assignment,
        AssignmentSubmissionService $service
    ) {
        $user = $request->user();
        $course = $assignment->course;

        // Pastikan hanya mahasiswa yang terdaftar dapat mengumpulkan tugas.
        abort_unless(
            $user->role === 'mahasiswa'
                && $user->courses()
                    ->whereKey($course->id)
                    ->exists(),
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        // Tugas harus sudah dipublikasikan.
        abort_unless(
            $assignment->status === 'published',
            403,
            'Tugas belum dapat dikumpulkan.'
        );

        // Pertahankan respons 403 untuk tugas terlambat yang tidak diizinkan.
        $isLate = now()->greaterThan($assignment->due_at);

        abort_unless(
            ! $isLate || $assignment->allow_late,
            403,
            'Batas waktu pengumpulan telah berakhir.'
        );

        $validated = $request->validated();

        // Service menangani pengumpulan pertama maupun pengumpulan ulang.
        // Saat mengumpulkan ulang, record lama diperbarui dan file lama dihapus.
        $submission = $service->submit(
            $assignment,
            $user,
            $validated['file'],
            $validated['note'] ?? null
        );

        $submission->load('student');

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode($submission->wasRecentlyCreated ? 201 : 200);
    }
}