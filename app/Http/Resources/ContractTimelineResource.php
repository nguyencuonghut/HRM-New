<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\ActivityLogDescription;

class ContractTimelineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->determineType(),
            'action' => $this->description,
            'subject_type' => $this->determineSubjectType(),
            'subject_info' => $this->getSubjectInfo(),
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

    private function determineSubjectType(): string
    {
        if (!$this->subject_type) return 'unknown';

        // Check ContractAppendix BEFORE Contract because ContractAppendix contains "Contract"
        if (str_contains($this->subject_type, 'ContractAppendix')) {
            return 'appendix';
        }

        if (str_contains($this->subject_type, 'Contract')) {
            return 'contract';
        }

        return 'other';
    }

    private function getSubjectInfo(): ?array
    {
        if (!$this->subject) return null;

        $subjectType = $this->determineSubjectType();

        if ($subjectType === 'contract') {
            return [
                'id' => $this->subject->id,
                'number' => $this->subject->contract_number ?? null,
            ];
        }

        if ($subjectType === 'appendix') {
            return [
                'id' => $this->subject->id,
                'appendix_no' => $this->subject->appendix_no ?? null,
                'type_label' => $this->subject->appendix_type?->label() ?? null,
            ];
        }

        return null;
    }

    private function determineType(): string
    {
        // Try to match enum values first
        try {
            $enum = ActivityLogDescription::tryFrom($this->description);
            if ($enum) {
                return $enum->type();
            }
        } catch (\Exception $e) {
            // Continue to legacy matching
        }

        // Legacy matching for old activity logs (backward compatibility)
        if ($this->description === 'Chấm dứt hợp đồng') {
            return 'TERMINATED';
        }

        if (str_contains($this->description, 'Phê duyệt bước')) {
            return 'APPROVED_STEP';
        }

        return 'OTHER';
    }
}
