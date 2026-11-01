<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReturnsApiValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    use ReturnsApiValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'Nilai wajib diisi.',
            'score.numeric' => 'Nilai harus berupa angka.',
            'score.min' => 'Nilai tidak boleh kurang dari 0.',
            'score.max' => 'Nilai tidak boleh lebih dari 100.',
            'feedback.string' => 'Feedback harus berupa teks.',
        ];
    }
}
