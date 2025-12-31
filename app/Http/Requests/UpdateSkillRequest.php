<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skillId = $this->route('skill');

        return [
            'category_id' => ['required', 'uuid', 'exists:skill_categories,id'],
            'code'        => ['nullable', 'string', 'max:255', Rule::unique('skills', 'code')->ignore($skillId)],
            'name'        => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists'   => 'Danh mục không tồn tại.',
            'code.unique'          => 'Mã kỹ năng đã tồn tại.',
            'name.required'        => 'Vui lòng nhập tên kỹ năng.',
            'name.max'             => 'Tên kỹ năng không được vượt quá 255 ký tự.',
        ];
    }
}
