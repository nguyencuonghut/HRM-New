<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceChangeRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,

            // Employee info
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
                'full_name' => $this->employee->full_name,
                'si_number' => $this->employee->si_number,
            ]),

            // Change info
            'change_type' => $this->change_type,
            'auto_reason' => $this->auto_reason,
            'auto_reason_label' => $this->getAutoReasonLabel(),

            // Insurance info
            'insurance_salary' => $this->insurance_salary,
            'adjusted_salary' => $this->adjusted_salary,
            'final_salary' => $this->getFinalSalary(),
            'has_social_insurance' => $this->has_social_insurance,
            'has_health_insurance' => $this->has_health_insurance,
            'has_unemployment_insurance' => $this->has_unemployment_insurance,

            'system_notes' => $this->system_notes,
            'effective_date' => $this->effective_date->format('d/m/Y'),

            // Approval info
            'approval_status' => $this->approval_status,
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),

            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('d/m/Y H:i'),
            'admin_notes' => $this->admin_notes,
            'adjustment_reason' => $this->adjustment_reason,

            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
