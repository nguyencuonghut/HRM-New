<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractAppendixRequest extends FormRequest
{
    public function authorize(): bool {
        $contract = $this->route('contract');
        return $this->user()->can('edit contracts') || $this->user()->can('approve contracts');
    }
    public function rules(): array {
        $contract = $this->route('contract');
        return [
            'appendix_no'   => ['required','string','max:100', Rule::unique('contract_appendixes','appendix_no')->where('contract_id',$contract->id)],
            'appendix_type' => ['required', Rule::in(['SALARY','ALLOWANCE','POSITION','DEPARTMENT','WORKING_TERMS','EXTENSION','OTHER'])],
            'source'        => ['nullable', Rule::in(['LEGACY','WORKFLOW'])],
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
        ];
    }
}
