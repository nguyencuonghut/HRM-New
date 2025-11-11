<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool {
        $employee = $this->route('employee');
        return $this->user()->can('create contracts') || $this->user()->can('editProfile',$employee);
    }

    public function rules(): array {
        return [
            'employee_id'       => ['required','uuid','exists:employees,id'],
            'contract_number'   => ['required','string','max:100','unique:contracts,contract_number'],
            'contract_type'     => ['required', Rule::in(['PROBATION','FIXED_TERM','INDEFINITE','SEASONAL','SERVICE','INTERNSHIP','PARTTIME'])],
            'status'            => ['nullable', Rule::in(['DRAFT','PENDING_APPROVAL','ACTIVE','SUSPENDED','TERMINATED','EXPIRED','CANCELLED'])],
            'sign_date'         => ['nullable','date'],
            'start_date'        => ['required','date'],
            'end_date'          => ['nullable','date','after_or_equal:start_date'],
            'probation_end_date'=> ['nullable','date','after_or_equal:start_date'],
            'base_salary'       => ['required','integer','min:0'],
            'insurance_salary'  => ['required','integer','min:0'],
            'position_allowance'=> ['nullable','integer','min:0'],
            'other_allowances'  => ['nullable','array'],
            'other_allowances.*.name' => ['required_with:other_allowances','string','max:100'],
            'other_allowances.*.amount' => ['required_with:other_allowances','integer','min:0'],
            'social_insurance'  => ['boolean'],
            'health_insurance'  => ['boolean'],
            'unemployment_insurance' => ['boolean'],
            'work_location'     => ['nullable','string','max:255'],
            'working_time'      => ['nullable','string','max:255'],
            'department_id'     => ['nullable','uuid'],
            'position_id'       => ['nullable','uuid'],
            'note'              => ['nullable','string','max:4000'],
        ];
    }

    public function attributes(): array {
        return [
            'employee_id'        => 'nhân viên',
            'contract_number'    => 'số hợp đồng',
            'contract_type'      => 'loại hợp đồng',
            'status'             => 'trạng thái hợp đồng',
            'sign_date'          => 'ngày ký',
            'start_date'         => 'ngày hiệu lực',
            'end_date'           => 'ngày kết thúc',
            'probation_end_date' => 'ngày kết thúc thử việc',
            'base_salary'       => 'lương cơ bản',
            'insurance_salary'  => 'lương đóng bảo hiểm',
            'position_allowance'=> 'phụ cấp vị trí',
            'other_allowances'  => 'phụ cấp khác',
            'social_insurance'  => 'bảo hiểm xã hội',
            'health_insurance'  => 'bảo hiểm y tế',
            'unemployment_insurance' => 'bảo hiểm thất nghiệp',
            'work_location'     => 'địa điểm làm việc',
            'working_time'      => 'thời gian làm việc',
            'department_id'     => 'phòng ban',
            'position_id'       => 'chức danh',
            'note'              => 'ghi chú',
        ];
    }

    public function messages(): array {
        return [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'employee_id.exists' => 'Nhân viên không tồn tại.',
            'contract_number.required' => 'Vui lòng nhập số hợp đồng.',
            'contract_number.unique' => 'Số hợp đồng đã tồn tại.',
            'contract_type.required' => 'Vui lòng chọn loại hợp đồng.',
            'start_date.required' => 'Vui lòng nhập ngày hiệu lực.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày hiệu lực.',
            'probation_end_date.after_or_equal' => 'Ngày kết thúc thử việc phải sau hoặc bằng ngày hiệu lực.',
            'base_salary.required' => 'Vui lòng nhập lương cơ bản.',
            'base_salary.integer' => 'Lương cơ bản phải là số nguyên.',
            'base_salary.min' => 'Lương cơ bản không được nhỏ hơn 0.',
            'insurance_salary.required' => 'Vui lòng nhập lương đóng bảo hiểm.',
            'insurance_salary.integer' => 'Lương đóng bảo hiểm phải là số nguyên.',
            'insurance_salary.min' => 'Lương đóng bảo hiểm không được nhỏ hơn 0.',
            'position_allowance.integer' => 'Phụ cấp vị trí phải là số nguyên.',
            'position_allowance.min' => 'Phụ cấp vị trí không được nhỏ hơn 0.',
            'other_allowances.array' => 'Phụ cấp khác phải là mảng.',
            'other_allowances.*.name.required_with' => 'Vui lòng nhập tên phụ cấp khác.',
            'other_allowances.*.amount.required_with' => 'Vui lòng nhập số tiền phụ cấp khác.',
            'other_allowances.*.amount.integer' => 'Số tiền phụ cấp khác phải là số nguyên.',
            'other_allowances.*.amount.min' => 'Số tiền phụ cấp khác không được nhỏ hơn 0.',
        ];
    }
}
