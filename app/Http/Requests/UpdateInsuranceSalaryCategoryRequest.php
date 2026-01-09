<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsuranceSalaryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        $categoryId = $this->route('insurance_salary_category');

        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('insurance_salary_categories', 'code')->ignore($categoryId)],
            'name' => ['required', 'string', 'max:100', Rule::unique('insurance_salary_categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã nhóm chức danh đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên nhóm chức danh.',
            'name.unique' => 'Tên nhóm chức danh đã tồn tại.',
            'display_order.min' => 'Thứ tự hiển thị phải lớn hơn hoặc bằng 0.',
            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
        ];
    }
}
