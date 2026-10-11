<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReturnsApiValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssignmentRequest extends FormRequest
{
    use ReturnsApiValidationErrors;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published'])],
        ];
    }
}
