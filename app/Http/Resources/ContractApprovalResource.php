<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractApprovalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'order' => $this->order,
            'approver_id' => $this->approver_id,
            'approver' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'email' => $this->approver->email,
                ];
            }),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'comments' => $this->comments,
            'approved_at' => $this->approved_at?->format('d/m/Y H:i'),
            'approved_at_timestamp' => $this->approved_at?->timestamp,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
