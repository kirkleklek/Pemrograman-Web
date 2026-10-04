<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'due_at' => $this->due_at?->toIso8601String(),
            'max_score' => $this->max_score,
            'allow_late' => $this->allow_late,
            'status' => $this->status,
        ];
    }
}