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
        $rules = [
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
            'department_id'  => ['nullable','uuid','exists:departments,id'],
            'position_id'    => ['nullable','uuid','exists:positions,id'],
            'working_time'   => ['nullable','string','max:255'],
            'work_location'  => ['nullable','string','max:255'],
            'note'           => ['nullable','string','max:4000'],
            'approval_note'  => ['sometimes','nullable','string','max:1000'],
            'status'         => ['sometimes', Rule::in(['DRAFT','PENDING_APPROVAL','ACTIVE','REJECTED','CANCELLED'])],
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
