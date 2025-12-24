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
        $rules = [
            'appendix_no'   => ['required','string','max:100', Rule::unique('contract_appendixes','appendix_no')->where('contract_id',$contract->id)],
            'appendix_type' => ['required', Rule::in(['SALARY','ALLOWANCE','POSITION','DEPARTMENT','WORKING_TERMS','EXTENSION','OTHER'])],
            'status'        => ['required', Rule::in(['DRAFT','PENDING_APPROVAL','ACTIVE','REJECTED','CANCELLED'])],
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
            'department_id'  => ['nullable','uuid','exists:departments,id'],
            'position_id'    => ['nullable','uuid','exists:positions,id'],
            'working_time'   => ['nullable','string','max:255'],
            'work_location'  => ['nullable','string','max:255'],
            'note'           => ['nullable','string','max:4000'],
        ];

        // Type-driven validation
        $type = $this->input('appendix_type');

        switch ($type) {
            case 'SALARY':
                $rules['base_salary'] = ['required','integer','min:1'];
                break;

            case 'ALLOWANCE':
                // At least one: position_allowance OR other_allowances
                $rules['position_allowance'] = ['required_without:other_allowances','nullable','integer','min:1'];
                $rules['other_allowances'] = ['required_without:position_allowance','nullable','array'];
                break;

            case 'POSITION':
                $rules['position_id'] = ['required','uuid','exists:positions,id'];
                break;

            case 'DEPARTMENT':
                $rules['department_id'] = ['required','uuid','exists:departments,id'];
                break;

            case 'WORKING_TERMS':
                $rules['working_time'] = ['required','string','max:255'];
                $rules['work_location'] = ['required','string','max:255'];
                break;

            case 'EXTENSION':
                $rules['end_date'] = ['required','date','after:effective_date'];
                break;

            case 'OTHER':
                $rules['summary'] = ['required','string','max:2000'];
                break;
        }

        return $rules;
    }
}
