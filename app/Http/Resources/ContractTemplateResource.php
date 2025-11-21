<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => (string) $this->id,
            'name'              => $this->name,
            'type'              => $this->type,
            'engine'            => $this->engine,
            'type_label'        => $this->typeLabel($this->type),
            'engine_label'      => $this->engineLabel($this->engine),
            'body_path'         => $this->body_path,
            'content'           => $this->content,
            'placeholders_json' => $this->placeholders_json,
            'is_default'        => (bool) $this->is_default,
            'is_active'         => (int) $this->is_active,
            'status_label'      => $this->is_active ? 'Hoạt động' : 'Ngừng dùng',
            'description'       => $this->description,
            'version'           => $this->version,
            'created_at'        => optional($this->created_at)->toDateTimeString(),
        ];
    }

    private function typeLabel(?string $type): string
    {
        return [
            'PROBATION'  => 'Thử việc',
            'FIXED_TERM' => 'Xác định thời hạn',
            'INDEFINITE' => 'Không xác định thời hạn',
            'SEASONAL'   => 'Thời vụ',
            'SERVICE'    => 'Cộng tác/Dịch vụ',
            'INTERNSHIP' => 'Thực tập',
            'PARTTIME'   => 'Bán thời gian',
        ][$type] ?? $type ?? '-';
    }

    private function engineLabel(?string $engine): string
    {
        return [
            'LIQUID'       => 'Liquid Template',
            'BLADE'        => 'Blade View',
            'HTML_TO_PDF'  => 'HTML to PDF',
            'DOCX_MERGE'   => 'DOCX Merge',
        ][$engine] ?? $engine ?? '-';
    }
}
