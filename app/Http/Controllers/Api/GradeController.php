<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Resources\GradeResource;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeController extends Controller
{
    /**
     * Memberikan atau memperbarui nilai submission.
     */
    public function update(
        GradeSubmissionRequest $request,
        Submission $submission
    ) {
        $user = $request->user();

        $submission->load('assignment.course');

        $existingGrade = Grade::query()
            ->where('submission_id', $submission->id)
            ->first();

        if ($existingGrade) {
            Gate::authorize('update', $existingGrade);
        } else {
            Gate::authorize('create', [
                Grade::class,
                $submission,
            ]);
        }

        $validated = $request->validated();

        $grade = Grade::updateOrCreate(
            [
                'submission_id' => $submission->id,
            ],
            [
                'graded_by' => $user->id,
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        $grade->load([
            'grader',
            'submission.assignment.course',
        ]);

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode(
                $grade->wasRecentlyCreated ? 201 : 200
            );
    }

    /**
     * Rekap nilai per mata kuliah untuk dosen pengampu.
     */
    public function courseRecap(
        Request $request,
        Course $course
    ) {
        Gate::authorize('viewAny', [Grade::class, $course]);

        abort_unless(
            $request->user()->role === 'dosen',
            403,
            'Endpoint ini hanya untuk dosen.'
        );

        $grades = Grade::query()
            ->whereHas('submission.assignment', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->with([
                'grader',
                'submission.student',
                'submission.assignment.course',
            ])
            ->orderByDesc('graded_at')
            ->paginate(20)
            ->withQueryString();

        return GradeResource::collection($grades);
    }

    /**
     * Nilai mahasiswa yang sedang login.
     */
    public function myGrades(Request $request)
    {
        abort_unless(
            $request->user()->role === 'mahasiswa',
            403,
            'Endpoint ini hanya untuk mahasiswa.'
        );

        $grades = Grade::query()
            ->whereHas('submission', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->whereHas('assignment.course.students', function ($courseQuery) use ($request) {
                        $courseQuery->where(
                            'users.id',
                            $request->user()->id
                        );
                    });
            })
            ->with([
                'grader',
                'submission.student',
                'submission.assignment.course',
            ])
            ->orderByDesc('graded_at')
            ->paginate(20)
            ->withQueryString();

        return GradeResource::collection($grades);
    }
}
