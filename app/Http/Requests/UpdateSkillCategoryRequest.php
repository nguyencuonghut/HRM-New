<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $skillCategory = $this->route('skill_category');
        return $this->user()->can('update', $skillCategory);
    }

    public function rules(): array
    {
        $skillCategoryId = $this->route('skill_category')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('skill_categories', 'name')->ignore($skillCategoryId)],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên nhóm kỹ năng.',
            'name.unique' => 'Tên nhóm kỹ năng đã tồn tại.',
            'is_active.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
