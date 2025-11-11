<?php

namespace App\Http\Resources;

use App\Enums\{ContractType, ContractStatus, ContractSource};
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
            ] : null,
            'department_id' => $this->department_id,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code ?? null,
            ] : null,
            'position_id' => $this->position_id,
            'position' => $this->position ? [
                'id' => $this->position->id,
                'title' => $this->position->title,
                'code' => $this->position->code ?? null,
            ] : null,
            'contract_number' => $this->contract_number,
            'contract_type' => $this->contract_type,
            'contract_type_label' => ContractType::tryFrom($this->contract_type)?->label(),
            'status' => $this->status,
            'status_label'        => ContractStatus::tryFrom($this->status)?->label(),
            'sign_date' => optional($this->sign_date)->toDateString(),
            'start_date'=> optional($this->start_date)->toDateString(),
            'end_date'  => optional($this->end_date)->toDateString(),
            'probation_end_date' => optional($this->probation_end_date)->toDateString(),
            'base_salary'=> (int)$this->base_salary,
            'insurance_salary'=> (int)$this->insurance_salary,
            'position_allowance'=> (int)$this->position_allowance,
            'other_allowances'=> $this->other_allowances ?: [],
            'social_insurance'=> (bool)$this->social_insurance,
            'health_insurance'=> (bool)$this->health_insurance,
            'unemployment_insurance'=> (bool)$this->unemployment_insurance,
            'work_location'=> $this->work_location,
            'working_time' => $this->working_time,
            'approver_id'  => $this->approver_id,
            'approved_at'  => optional($this->approved_at)->toDateTimeString(),
            'rejected_at'  => optional($this->rejected_at)->toDateTimeString(),
            'approval_note'=> $this->approval_note,
            'terminated_at'=> optional($this->terminated_at)->toDateString(),
            'termination_reason'=> $this->termination_reason,
            'source'              => $this->source,
            'source_label'        => ContractSource::tryFrom($this->source)?->label(),
            'generated_pdf_path' => $this->generated_pdf_path ? asset("storage/{$this->generated_pdf_path}") : null,
            'signed_file_path'   => $this->signed_file_path ? asset("storage/{$this->signed_file_path}") : null,
            'note' => $this->note,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
