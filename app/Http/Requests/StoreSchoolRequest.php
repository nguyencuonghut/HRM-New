<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\School::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable','string','max:50'],
            'name' => ['required','string','max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên trường là bắt buộc.',
        ];
    }
}
