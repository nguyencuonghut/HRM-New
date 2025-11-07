<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        return $this->user()->can('editProfile', $employee);
    }

    public function rules(): array
    {
        $employeeId = optional($this->route('employee'))->id;

        return [
            'skill_id' => [
                'required','uuid',
                // đảm bảo 1 kỹ năng chỉ gán 1 lần cho NV
                Rule::unique('employee_skills','skill_id')
                    ->where('employee_id', $employeeId),
            ],
            'level' => ['nullable','integer','min:0','max:5'],
            'years' => ['nullable','integer','min:0'],
            'note'  => ['nullable','string','max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'skill_id' => 'kỹ năng',
            'level' => 'mức độ',
            'years' => 'số năm kinh nghiệm',
            'note' => 'ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'skill_id.required' => 'Vui lòng chọn kỹ năng.',
            'skill_id.uuid' => 'Kỹ năng không hợp lệ.',
            'skill_id.unique' => 'Kỹ năng đã được gán cho nhân viên này.',
            'level.integer' => 'Mức độ phải là số nguyên.',
            'level.min' => 'Mức độ không được nhỏ hơn 0.',
            'level.max' => 'Mức độ không được lớn hơn 5.',
            'years.integer' => 'Số năm kinh nghiệm phải là số nguyên.',
            'years.min' => 'Số năm kinh nghiệm không được nhỏ hơn 0.',
            'note.max' => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
