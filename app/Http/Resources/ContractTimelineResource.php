<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractTimelineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->determineType(),
            'action' => $this->description,
            'user' => [
                'id' => $this->causer?->id,
                'name' => $this->causer?->name ?? 'System',
                'email' => $this->causer?->email ?? '-',
            ],
            'comments' => $this->properties['comments'] ?? null,
            'level' => $this->properties['level'] ?? null,
            'contract_number' => $this->properties['contract_number'] ?? null,
            'timestamp' => $this->created_at->format('d/m/Y H:i'),
            'timestamp_unix' => $this->created_at->timestamp,
        ];
    }

    private function determineType(): string
    {
        // Check termination first
        if ($this->description === 'Chấm dứt hợp đồng') {
            return 'TERMINATED';
        }

        // Check approval step pattern
        if (str_contains($this->description, 'Phê duyệt bước')) {
            return 'APPROVED_STEP';
        }

        return match($this->description) {
            'created' => 'CREATED',
            'updated' => 'UPDATED',
            'Gửi phê duyệt' => 'SUBMITTED',
            'Phê duyệt hoàn tất - Hợp đồng hiệu lực' => 'APPROVED_FINAL',
            'Từ chối phê duyệt' => 'REJECTED',
            'Thu hồi yêu cầu phê duyệt' => 'RECALLED',
            'generated' => 'GENERATED_PDF',
            'CONTRACT_RENEWAL_REQUESTED' => 'CONTRACT_RENEWAL_REQUESTED',
            'CONTRACT_RENEWAL_APPROVED' => 'CONTRACT_RENEWAL_APPROVED',
            'CONTRACT_RENEWAL_REJECTED' => 'CONTRACT_RENEWAL_REJECTED',
            default => 'OTHER',
        };
    }
}
