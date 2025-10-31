<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'                 => ['nullable','integer'],
            'employee_code'           => ['required','string','max:100','unique:employees,employee_code'],
            'full_name'               => ['required','string','max:255'],
            'dob'                     => ['nullable','date'],
            'gender'                  => ['nullable','in:MALE,FEMALE,OTHER'],
            'marital_status'          => ['nullable','in:SINGLE,MARRIED,DIVORCED,WIDOWED'],
            'avatar'                  => ['nullable','string','max:500'],
            'cccd'                    => ['nullable','string','max:50'],
            'cccd_issued_on'          => ['nullable','date'],
            'cccd_issued_by'          => ['nullable','string','max:255'],
            'ward_id'                 => ['nullable','uuid'],
            'address_street'          => ['nullable','string','max:255'],
            'temp_ward_id'            => ['nullable','uuid'],
            'temp_address_street'     => ['nullable','string','max:255'],
            'phone'                   => ['nullable','string','max:50'],
            'emergency_contact_phone' => ['nullable','string','max:50'],
            'personal_email'          => ['nullable','email','max:255'],
            'company_email'           => ['nullable','email','max:255'],
            'hire_date'               => ['nullable','date'],
            'status'                  => ['required','in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'si_number'               => ['nullable','string','max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.required' => 'Mã nhân viên là bắt buộc.',
            'employee_code.unique'   => 'Mã nhân viên đã tồn tại.',
            'full_name.required'     => 'Họ tên là bắt buộc.',
            'status.required'        => 'Trạng thái là bắt buộc.',
        ];
    }
}
