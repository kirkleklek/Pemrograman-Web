<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sks' => $this->sks,
            'status' => $this->status,

            'lecturer' => UserResource::make(
                $this->whenLoaded('lecturer')
            ),

            'materials_count' => $this->when(
                isset($this->materials_count),
                $this->materials_count
            ),

            'assignments_count' => $this->when(
                isset($this->assignments_count),
                $this->assignments_count
            ),
        ];
    }
}