<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBenefitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:benefit_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã loại phúc lợi.',
            'code.unique' => 'Mã loại phúc lợi đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên loại phúc lợi.',
        ];
    }
}
