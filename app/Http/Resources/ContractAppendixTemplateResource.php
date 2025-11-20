<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAppendixTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => (string) $this->id,
            'name'              => $this->name,
            'code'              => $this->code,
            'appendix_type'     => $this->appendix_type,
            'appendix_type_label' => $this->appendixTypeLabel($this->appendix_type),
            'engine'            => $this->engine,
            'engine_label'      => 'DOCX Merge',
            'body_path'         => $this->body_path,
            'content'           => $this->content,
            'placeholders_json' => $this->placeholders_json,
            'description'       => $this->description,
            'is_default'        => (bool) $this->is_default,
            'is_active'         => (int) $this->is_active,
            'status_label'      => $this->is_active ? 'Hoạt động' : 'Ngừng dùng',
            'version'           => $this->version,
            'created_at'        => optional($this->created_at)->toDateTimeString(),
            'updated_at'        => optional($this->updated_at)->toDateTimeString(),
        ];
    }

    private function appendixTypeLabel(?string $type): string
    {
        return [
            'SALARY'        => 'Điều chỉnh lương',
            'ALLOWANCE'     => 'Điều chỉnh phụ cấp',
            'POSITION'      => 'Điều chỉnh chức danh',
            'DEPARTMENT'    => 'Điều chuyển đơn vị',
            'WORKING_TERMS' => 'Điều chỉnh điều kiện làm việc',
            'EXTENSION'     => 'Gia hạn hợp đồng',
            'OTHER'         => 'Khác',
        ][$type] ?? $type ?? '-';
    }
}
