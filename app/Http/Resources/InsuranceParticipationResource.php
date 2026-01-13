<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for InsuranceParticipation
 * Transforms insurance participation data for frontend
 */
class InsuranceParticipationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'contract_number' => $this->contract?->contract_number,
            'participation_start_date' => $this->participation_start_date?->format('Y-m-d'),
            'participation_end_date' => $this->participation_end_date?->format('Y-m-d'),
            'insurance_salary' => $this->insurance_salary,
            'insurance_salary_formatted' => number_format($this->insurance_salary, 0, ',', '.') . ' đ',
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Components details
            'components' => $this->whenLoaded('components', function () {
                return $this->components->map(function ($pc) {
                    return [
                        'id' => $pc->id,
                        'component_id' => $pc->component_id,
                        'component_code' => $pc->component->code,
                        'component_name' => $pc->component->name_vi,
                        'rate_total' => $pc->rate_total,
                        'rate_total_formatted' => number_format($pc->rate_total * 100, 2) . '%',
                        'base_type' => $pc->base_type,
                        'base_type_label' => $pc->base_type === 'INSURANCE_SALARY' ? 'Theo lương BH' : 'Cố định',
                        'base_used' => $pc->base_type === 'FIXED_AMOUNT' ? $pc->base_amount : $this->insurance_salary,
                        'base_used_formatted' => number_format($pc->base_type === 'FIXED_AMOUNT' ? $pc->base_amount : $this->insurance_salary, 0, ',', '.') . ' đ',
                        'amount_total' => ($pc->base_type === 'FIXED_AMOUNT' ? $pc->base_amount : $this->insurance_salary) * $pc->rate_total,
                        'amount_total_formatted' => number_format(($pc->base_type === 'FIXED_AMOUNT' ? $pc->base_amount : $this->insurance_salary) * $pc->rate_total, 0, ',', '.') . ' đ',
                        'note' => $pc->note,
                    ];
                });
            }),
        ];
    }

    protected function getStatusLabel()
    {
        return match($this->status) {
            'ACTIVE' => 'Đang hiệu lực',
            'SUSPENDED' => 'Tạm ngưng',
            'TERMINATED' => 'Đã kết thúc',
            default => $this->status,
        };
    }
}
