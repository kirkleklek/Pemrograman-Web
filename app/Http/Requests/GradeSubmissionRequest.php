<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GradeSubmissionRequest extends FormRequest
{
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

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}