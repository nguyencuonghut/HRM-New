<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeAnnualReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kpi_avg_score' => ['required', 'numeric', 'min:0'],
            'final_rating' => ['required', 'in:A,B,C,D'],
            'final_score' => ['nullable', 'numeric', 'min:0'],
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
            'kpi_avg_score.required' => 'Vui lòng nhập điểm KPI trung bình.',
            'kpi_avg_score.numeric' => 'Điểm KPI trung bình phải là số.',
            'kpi_avg_score.min' => 'Điểm KPI trung bình phải từ 0 trở lên.',
            'final_rating.required' => 'Vui lòng chọn xếp loại.',
            'final_rating.in' => 'Xếp loại không hợp lệ.',
            'final_score.numeric' => 'Điểm tổng phải là số.',
            'final_score.min' => 'Điểm tổng phải từ 0 trở lên.',
        ];
    }
}
