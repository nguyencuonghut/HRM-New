<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\EducationLevel::class);
    }

    public function rules(): array
    {
        return [
            'code'        => ['nullable','string','max:50'],
            'name'        => ['required','string','max:255'],
            'order_index' => ['nullable','integer','min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên trình độ là bắt buộc.',
        ];
    }
}
