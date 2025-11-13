<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => (string) $this->id,
            'name'          => $this->name,
            'type'          => $this->type,
            'engine'        => $this->engine,
            'type_label'    => $this->typeLabel($this->type),
            'body_path'     => $this->body_path,
            'is_default'    => (bool) $this->is_default,
            'is_active'     => (int) $this->is_active,
            'status_label'  => $this->is_active ? 'Hoạt động' : 'Ngừng dùng',
            'description'   => $this->description,
            'created_at'    => optional($this->created_at)->toDateTimeString(),
        ];
    }

    private function typeLabel(?string $type): string
    {
        return [
            'PROBATION'  => 'Thử việc',
            'FIXED_TERM' => 'Xác định thời hạn',
            'INDEFINITE' => 'Không xác định thời hạn',
            'SERVICE'    => 'Cộng tác/Dịch vụ',
        ][$type] ?? $type ?? '-';
    }
}
