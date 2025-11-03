<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'insurance_base_salary' => ['nullable', 'numeric', 'min:0'],
            'position_salary' => ['nullable', 'numeric', 'min:0'],
            'competency_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'department_id' => 'phòng ban',
            'title' => 'tên chức vụ',
            'level' => 'cấp bậc',
            'insurance_base_salary' => 'lương cơ bản đóng bảo hiểm',
            'position_salary' => 'lương chức vụ',
            'competency_salary' => 'lương năng lực',
            'allowance' => 'phụ cấp',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'department_id.exists' => 'Phòng ban không tồn tại.',
            'title.required' => 'Vui lòng nhập tên chức vụ.',
            'title.max' => 'Tên chức vụ không được vượt quá 255 ký tự.',
            '*.numeric' => ':attribute phải là số.',
            '*.min' => ':attribute không được nhỏ hơn :min.',
        ];
    }
}
