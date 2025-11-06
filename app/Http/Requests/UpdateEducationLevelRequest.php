<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $level = $this->route('education_level');
        return $this->user()->can('update', $level);
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
