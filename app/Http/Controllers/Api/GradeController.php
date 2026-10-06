<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function update(
        GradeSubmissionRequest $request,
        Submission $submission
    ) {
        $user = $request->user();

        $submission->load('assignment.course');

        $course = $submission->assignment->course;

        abort_unless(
            $user->role === 'dosen'
                && $course->lecturer_id === $user->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

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

        $grade->load('grader');

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode(
                $grade->wasRecentlyCreated ? 201 : 200
            );
    }
}