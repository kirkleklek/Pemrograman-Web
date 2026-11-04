<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReturnsApiValidationErrors;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    use ReturnsApiValidationErrors;

    public function authorize(): bool
    {
        $user = $this->user();
        $submission = $this->route('submission');

        if (! $user || ! $submission instanceof Submission) {
            return false;
        }

        $submission->loadMissing('assignment.course');

        $existingGrade = Grade::query()
            ->where('submission_id', $submission->id)
            ->first();

        if ($existingGrade) {
            return $user->can('update', $existingGrade);
        }

        return $user->can('create', [Grade::class, $submission]);
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
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
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
            'feedback.max' => 'Feedback maksimal 5000 karakter.',
        ];
    }
}