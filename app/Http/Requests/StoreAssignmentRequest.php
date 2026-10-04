<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dosen';
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(
            'Anda tidak memiliki akses ke sumber daya ini.'
        );
    }

    public function rules(): array
    {
        return [
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'instructions' => [
                'required',
                'string',
            ],
            'due_at' => [
                'required',
                'date',
            ],
            'max_score' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'allow_late' => [
                'sometimes',
                'boolean',
            ],
            'status' => [
                'required',
                Rule::in(['draft', 'published']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Mata kuliah wajib dipilih.',
            'course_id.exists' => 'Mata kuliah tidak ditemukan.',
            'title.required' => 'Judul tugas wajib diisi.',
            'instructions.required' => 'Instruksi tugas wajib diisi.',
            'due_at.required' => 'Deadline tugas wajib diisi.',
            'due_at.date' => 'Format deadline tidak valid.',
            'max_score.required' => 'Nilai maksimum wajib diisi.',
            'max_score.min' => 'Nilai maksimum minimal 1.',
            'max_score.max' => 'Nilai maksimum maksimal 100.',
            'status.required' => 'Status tugas wajib diisi.',
            'status.in' => 'Status tugas harus draft atau published.',
        ];
    }
}