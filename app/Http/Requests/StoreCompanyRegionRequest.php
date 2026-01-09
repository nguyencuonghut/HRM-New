<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region' => ['required', 'integer', 'min:1', 'max:4'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'region.required' => 'Vui lòng chọn vùng BHXH.',
            'region.integer' => 'Vùng BHXH phải là số nguyên.',
            'region.min' => 'Vùng BHXH phải từ 1 đến 4.',
            'region.max' => 'Vùng BHXH phải từ 1 đến 4.',
            'effective_from.required' => 'Vui lòng chọn ngày hiệu lực.',
            'effective_from.date' => 'Ngày hiệu lực không hợp lệ.',
            'effective_to.date' => 'Ngày kết thúc không hợp lệ.',
            'effective_to.after' => 'Ngày kết thúc phải sau ngày hiệu lực.',
            'note.max' => 'Ghi chú không được quá 1000 ký tự.',
        ];
    }
}
