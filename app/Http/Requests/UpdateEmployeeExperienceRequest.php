<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee   = $this->route('employee');
        $experience = $this->route('experience');
        return $this->user()->can('editProfile', $employee)
            && $this->user()->can('ownEmployeeItem', [$employee, $experience]);
    }

    public function rules(): array
    {
        return [
            'company_name'   => ['required','string','max:255'],
            'position_title' => ['required','string','max:255'],
            'start_date'     => ['nullable','date'],
            'end_date'       => ['nullable','date','after_or_equal:start_date'],
            'is_current'     => ['boolean'],
            'responsibilities' => ['nullable','string','max:4000'],
            'achievements'     => ['nullable','string','max:4000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name'   => 'tên công ty',
            'position_title' => 'chức danh',
            'start_date'     => 'ngày bắt đầu',
            'end_date'       => 'ngày kết thúc',
            'is_current'     => 'hiện tại',
            'responsibilities' => 'trách nhiệm',
            'achievements'     => 'thành tựu',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'   => 'Vui lòng nhập tên công ty.',
            'company_name.max'        => 'Tên công ty không được vượt quá 255 ký tự.',
            'position_title.required' => 'Vui lòng nhập chức danh.',
            'position_title.max'      => 'Chức danh không được vượt quá 255 ký tự.',
            'start_date.date'         => 'Ngày bắt đầu không hợp lệ.',
            'end_date.date'           => 'Ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'responsibilities.max'    => 'Trách nhiệm không được vượt quá 4000 ký tự.',
            'achievements.max'        => 'Thành tựu không được vượt quá 4000 ký tự.',
        ];
    }
}
