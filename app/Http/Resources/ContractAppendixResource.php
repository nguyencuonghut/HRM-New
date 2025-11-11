<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractAppendixResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'appendix_no' => $this->appendix_no,
            'appendix_type' => $this->appendix_type,
            'source' => $this->source,
            'title' => $this->title,
            'summary' => $this->summary,
            'effective_date' => optional($this->effective_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'status' => $this->status,
            'approver_id' => $this->approver_id,
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'rejected_at' => optional($this->rejected_at)->toDateTimeString(),
            'approval_note' => $this->approval_note,
            'base_salary' => $this->base_salary ? (int)$this->base_salary : null,
            'insurance_salary' => $this->insurance_salary ? (int)$this->insurance_salary : null,
            'position_allowance' => $this->position_allowance ? (int)$this->position_allowance : null,
            'other_allowances' => $this->other_allowances ?: [],
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'working_time' => $this->working_time,
            'work_location' => $this->work_location,
            'note' => $this->note,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
