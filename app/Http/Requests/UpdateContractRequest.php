<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool {
        $contract = $this->route('contract');
        return $this->user()->can('update',$contract);
    }
    public function rules(): array {
        $contract = $this->route('contract');
        return [
            'contract_number'   => ['required','string','max:100', Rule::unique('contracts','contract_number')->ignore($contract?->id)],
            'contract_type'     => ['required', Rule::in(['PROBATION','FIXED_TERM','INDEFINITE','SEASONAL','SERVICE','INTERNSHIP','PARTTIME'])],
            'status'            => ['required', Rule::in(['DRAFT','PENDING_APPROVAL','ACTIVE','SUSPENDED','TERMINATED','EXPIRED','CANCELLED'])],
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
            'approval_note'     => ['nullable','string','max:1000'],
            'terminated_at'     => ['nullable','date'],
            'termination_reason'=> ['nullable','string','max:255'],
            'note'              => ['nullable','string','max:4000'],
        ];
    }

    public function attributes(): array {
        return [
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
            'approval_note'     => 'ghi chú phê duyệt',
            'terminated_at'     => 'ngày chấm dứt',
            'termination_reason'=> 'lý do chấm dứt',
            'note'              => 'ghi chú',
        ];
    }

    public function messages(): array {
        return [
            'contract_number.required' => 'Vui lòng nhập số hợp đồng.',
            'contract_number.unique' => 'Số hợp đồng đã tồn tại.',
            'contract_type.required' => 'Vui lòng chọn loại hợp đồng.',
            'start_date.required' => 'Vui lòng nhập ngày hiệu lực.',
        ];
    }
};
