<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest              
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sks' => ['required', 'integer', 'between:1,6'],
            'lecturer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'dosen')
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', 'in:draft,active,archived'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.unique' => 'Kode mata kuliah ini sudah dipakai.',
            'code.max' => 'Kode mata kuliah maksimal 20 karakter.',

            'name.required' => 'Nama mata kuliah wajib diisi.',
            'name.max' => 'Nama mata kuliah maksimal 150 karakter.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'sks.required' => 'SKS wajib diisi.',
            'sks.integer' => 'SKS harus berupa angka.',
            'sks.between' => 'SKS harus antara 1 sampai 6.',

            'lecturer_id.required' => 'Dosen wajib dipilih.',
            'lecturer_id.exists' => 'Dosen yang dipilih tidak valid.',

            'status.required' => 'Status mata kuliah wajib diisi.',
            'status.in' => 'Status harus draft, active, atau archived.',
        ];
    }
}