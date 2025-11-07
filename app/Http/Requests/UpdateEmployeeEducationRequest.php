<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EmployeeEducation;

class UpdateEmployeeEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee   = $this->route('employee');
        $education  = $this->route('education');
        return $this->user()->can('editProfile', $employee) &&
               $this->user()->can('ownEmployeeItem', [$employee, $education]);
    }

    public function rules(): array
    {
        return [
            'education_level_id' => ['nullable','uuid','exists:education_levels,id'],
            'school_id'          => ['nullable','uuid','exists:schools,id'],
            'major'              => ['nullable','string','max:255'],
            'start_year'         => ['nullable','integer','min:1900','max:2100'],
            'end_year'           => ['nullable','integer','min:1900','max:2100','gte:start_year'],
            'study_form'         => ['nullable','in:FULLTIME,PARTTIME,ONLINE'],
            'certificate_no'     => ['nullable','string','max:100'],
            'graduation_date'    => ['nullable','date'],
            'grade'              => ['nullable','string','max:100'],
            'note'               => ['nullable','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'education_level_id.exists' => 'Trình độ học vấn không tồn tại.',
            'school_id.exists' => 'Trường học không tồn tại.',
            'major.max' => 'Chuyên ngành không được vượt quá 255 ký tự.',
            'start_year.integer' => 'Năm bắt đầu phải là số nguyên.',
            'start_year.min' => 'Năm bắt đầu không được nhỏ hơn 1900.',
            'start_year.max' => 'Năm bắt đầu không được lớn hơn 2100.',
            'end_year.integer' => 'Năm kết thúc phải là số nguyên.',
            'end_year.min' => 'Năm kết thúc không được nhỏ hơn 1900.',
            'end_year.max' => 'Năm kết thúc không được lớn hơn 2100.',
            'end_year.gte' => 'Năm kết thúc phải lớn hơn hoặc bằng năm bắt đầu.',
            'study_form.in' => 'Hình thức học không hợp lệ.',
            'certificate_no.max' => 'Số chứng chỉ không được vượt quá 100 ký tự.',
            'graduation_date.date' => 'Ngày tốt nghiệp không hợp lệ.',
            'grade.max' => 'Xếp loại không được vượt quá 100 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'education_level_id' => 'trình độ học vấn',
            'school_id'          => 'trường học',
            'major'              => 'chuyên ngành',
            'start_year'         => 'năm bắt đầu',
            'end_year'           => 'năm kết thúc',
            'study_form'         => 'hình thức học',
            'certificate_no'     => 'số chứng chỉ',
            'graduation_date'    => 'ngày tốt nghiệp',
            'grade'              => 'xếp loại',
            'note'               => 'ghi chú',
        ];
    }
}
