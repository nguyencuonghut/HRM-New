<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractTemplateContentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'engine'  => ['required', Rule::in(['LIQUID','BLADE'])], // chỉ cho edit nếu LIQUID (validate ở Controller)
            'content' => ['nullable','string'], // có thể rỗng để tạm lưu
            'data'    => ['sometimes','array'], // cho preview
        ];
    }

    public function messages(): array
    {
        return [
            'engine.required' => 'Thiếu thông tin engine.',
        ];
    }
}
