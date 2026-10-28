<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'graded_by' => $this->graded_by,

            'grader' => UserResource::make(
                $this->whenLoaded('grader')
            ),

            'score' => $this->score,
            'feedback' => $this->feedback,

            'graded_at' => $this->graded_at?->toIso8601String(),

            'submission' => $this->whenLoaded(
                'submission',
                function () {
                    $submission = $this->submission;

                    return [
                        'id' => $submission->id,
                        'user_id' => $submission->user_id,

                        'student' => UserResource::make(
                            $submission->relationLoaded('student')
                                ? $submission->student
                                : null
                        ),

                        'assignment' => $submission->relationLoaded('assignment')
                            ? [
                                'id' => $submission->assignment->id,
                                'title' => $submission->assignment->title,

                                'course' => $submission->assignment->relationLoaded('course')
                                    ? [
                                        'id' => $submission->assignment->course->id,
                                        'code' => $submission->assignment->course->code,
                                        'name' => $submission->assignment->course->name,
                                    ]
                                    : null,
                            ]
                            : null,
                    ];
                }
            ),
        ];
    }
}
