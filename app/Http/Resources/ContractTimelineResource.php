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
        return match($this->description) {
            'created' => 'CREATED',
            'updated' => 'UPDATED',
            'Gửi phê duyệt' => 'SUBMITTED',
            'Phê duyệt hoàn tất - Hợp đồng hiệu lực' => 'APPROVED_FINAL',
            'Từ chối phê duyệt' => 'REJECTED',
            'Thu hồi yêu cầu phê duyệt' => 'RECALLED',
            'generated' => 'GENERATED_PDF',
            default => str_contains($this->description, 'Phê duyệt bước') ? 'APPROVED_STEP' : 'OTHER',
        };
    }
}
