<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\EmployeeAssignment::class);
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required','uuid','exists:employees,id'],
            'department_id' => ['required','uuid','exists:departments,id'],
            'position_id'   => ['nullable','uuid','exists:positions,id'],
            'is_primary'    => ['required','boolean'],
            'role_type'     => ['required','in:HEAD,DEPUTY,MEMBER'],
            'start_date'    => ['nullable','date'],
            'end_date'      => ['nullable','date','after_or_equal:start_date'],
            'status'        => ['required','in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'department_id.required' => 'Vui lòng chọn phòng/ban.',
            'role_type.in' => 'Vai trò không hợp lệ.',
            'status.in'    => 'Trạng thái không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải >= ngày bắt đầu.',
        ];
    }
}
