<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeRewardDisciplineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_severity' => $this->type->severity(),
            'type_icon' => $this->type->icon(),
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'decision_no' => $this->decision_no,
            'decision_date' => $this->decision_date->format('d/m/Y'),
            'decision_date_raw' => $this->decision_date->format('Y-m-d'),
            'effective_date' => $this->effective_date->format('d/m/Y'),
            'effective_date_raw' => $this->effective_date->format('Y-m-d'),
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'description' => $this->description,
            'note' => $this->note,
            'evidence_files' => $this->evidence_files,
            'issued_by' => $this->issued_by,
            'issued_by_name' => $this->issuedBy ? $this->issuedBy->full_name : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_severity' => $this->status->severity(),
            'related_contract_id' => $this->related_contract_id,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
