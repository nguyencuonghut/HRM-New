<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBenefitPayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'benefit_type_id' => $this->benefit_type_id,
            'paid_date' => optional($this->paid_date)->toDateString(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'note' => $this->note,
            'paid_by' => $this->paid_by,
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no,
            'source' => $this->source,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
            ]),
            'benefit_type' => $this->whenLoaded('benefitType', fn() => [
                'id' => $this->benefitType->id,
                'code' => $this->benefitType->code,
                'name' => $this->benefitType->name,
            ]),
            'paid_by_user' => $this->whenLoaded('paidByUser', fn() => [
                'id' => $this->paidByUser->id,
                'name' => $this->paidByUser->name,
            ]),
        ];
    }
}
