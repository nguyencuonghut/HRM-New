<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractAppendixRequest extends FormRequest
{
    public function authorize(): bool {
        $appendix = $this->route('appendix');
        return $this->user()->can('update', $appendix);
    }
    public function rules(): array {
        $appendix = $this->route('appendix');
        return [
            'appendix_no'   => ['required','string','max:100', Rule::unique('contract_appendixes','appendix_no')
                ->ignore($appendix?->id)->where('contract_id', $appendix?->contract_id)],
            'appendix_type' => ['required', Rule::in(['SALARY','ALLOWANCE','POSITION','DEPARTMENT','WORKING_TERMS','EXTENSION','OTHER'])],
            'title'         => ['nullable','string','max:255'],
            'summary'       => ['nullable','string','max:2000'],
            'effective_date'=> ['required','date'],
            'end_date'      => ['nullable','date','after_or_equal:effective_date'],
            'base_salary'   => ['nullable','integer','min:0'],
            'insurance_salary' => ['nullable','integer','min:0'],
            'position_allowance'=> ['nullable','integer','min:0'],
            'other_allowances'  => ['nullable','array'],
            'other_allowances.*.name'   => ['required_with:other_allowances','string','max:100'],
            'other_allowances.*.amount' => ['required_with:other_allowances','integer','min:0'],
            'department_id'  => ['nullable','uuid'],
            'position_id'    => ['nullable','uuid'],
            'working_time'   => ['nullable','string','max:255'],
            'work_location'  => ['nullable','string','max:255'],
            'note'           => ['nullable','string','max:4000'],
            'approval_note'  => ['sometimes','nullable','string','max:1000'],
            'status'         => ['sometimes', Rule::in(['DRAFT','PENDING_APPROVAL','ACTIVE','REJECTED','CANCELLED'])],
        ];
    }
}
