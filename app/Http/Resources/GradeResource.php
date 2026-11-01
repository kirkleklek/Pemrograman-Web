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
        ];
    }
}