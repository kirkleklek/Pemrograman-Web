<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
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
            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'instructions' => [
                'sometimes',
                'string',
            ],
            'due_at' => [
                'sometimes',
                'date',
            ],
            'max_score' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'allow_late' => [
                'sometimes',
                'boolean',
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'Judul tugas harus berupa teks.',
            'title.max' => 'Judul tugas maksimal 255 karakter.',
            'instructions.string' => 'Instruksi tugas harus berupa teks.',
            'due_at.date' => 'Format deadline tidak valid.',
            'max_score.integer' => 'Nilai maksimum harus berupa angka.',
            'max_score.min' => 'Nilai maksimum minimal 1.',
            'max_score.max' => 'Nilai maksimum maksimal 100.',
            'status.in' => 'Status tugas harus draft atau published.',
        ];
    }
}