<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee      = $this->route('employee');
        $employeeSkill = $this->route('employeeSkill');
        return $this->user()->can('editProfile', $employee)
            && $this->user()->can('ownEmployeeItem', [$employee, $employeeSkill]);
    }

    public function rules(): array
    {
        $employeeId = optional($this->route('employee'))->id;
        $currentId  = optional($this->route('employeeSkill'))->id;

        return [
            'skill_id' => [
                'sometimes','uuid',
                Rule::unique('employee_skills','skill_id')
                    ->ignore($currentId)
                    ->where('employee_id', $employeeId),
            ],
            'level' => ['sometimes','integer','min:0','max:5'],
            'years' => ['sometimes','integer','min:0'],
            'note'  => ['sometimes','nullable','string','max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'skill_id' => 'kỹ năng',
            'level'    => 'mức độ',
            'years'    => 'số năm kinh nghiệm',
            'note'     => 'ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'skill_id.unique' => 'Kỹ năng đã được gán cho nhân viên này.',
            'level.integer'   => 'Mức độ phải là số nguyên.',
            'level.min'       => 'Mức độ không được nhỏ hơn :min.',
            'level.max'       => 'Mức độ không được lớn hơn :max.',
            'years.integer'   => 'Số năm kinh nghiệm phải là số nguyên.',
            'years.min'       => 'Số năm kinh nghiệm không được nhỏ hơn :min.',
            'note.max'        => 'Ghi chú không được vượt quá :max ký tự.',
        ];
    }
}
