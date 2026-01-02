<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeKpiMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kpiId = $this->route('employee_kpi_month');

        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'kpi_score' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'employee_id.exists' => 'Nhân viên không tồn tại.',
            'year.required' => 'Vui lòng nhập năm.',
            'year.integer' => 'Năm phải là số nguyên.',
            'year.min' => 'Năm phải từ 2000 trở lên.',
            'year.max' => 'Năm không được vượt quá 2100.',
            'month.required' => 'Vui lòng chọn tháng.',
            'month.integer' => 'Tháng phải là số nguyên.',
            'month.min' => 'Tháng phải từ 1 đến 12.',
            'month.max' => 'Tháng phải từ 1 đến 12.',
            'kpi_score.required' => 'Vui lòng nhập điểm KPI.',
            'kpi_score.numeric' => 'Điểm KPI phải là số.',
            'kpi_score.min' => 'Điểm KPI phải từ 0 trở lên.',
        ];
    }
}
