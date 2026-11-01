<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReturnsApiValidationErrors;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    use ReturnsApiValidationErrors;
    public function authorize(): bool
    {
        return $this->user()?->role === 'mahasiswa';
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Berkas tugas wajib diunggah.',
            'file.file' => 'Berkas tugas yang diunggah tidak valid.',
            'note.string' => 'Catatan harus berupa teks.',
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(
            'Anda tidak memiliki akses ke sumber daya ini.'
        );
    }
}