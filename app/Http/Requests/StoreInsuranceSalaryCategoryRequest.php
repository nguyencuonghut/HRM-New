<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInsuranceSalaryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:insurance_salary_categories,code'],
            'name' => ['required', 'string', 'max:100', 'unique:insurance_salary_categories,name'],
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
