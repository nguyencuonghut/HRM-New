<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ContractAppendixResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'appendix_no' => $this->appendix_no,
            'appendix_type' => $this->appendix_type,
            'appendix_type_label' => $this->getAppendixTypeLabel(),
            'source' => $this->source,
            'title' => $this->title,
            'summary' => $this->summary,
            'effective_date' => optional($this->effective_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'approver_id' => $this->approver_id,
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'rejected_at' => optional($this->rejected_at)->toDateTimeString(),
            'approval_note' => $this->approval_note,
            'base_salary' => $this->base_salary ? (int)$this->base_salary : null,
            'insurance_salary' => $this->insurance_salary ? (int)$this->insurance_salary : null,
            'position_allowance' => $this->position_allowance ? (int)$this->position_allowance : null,
            'other_allowances' => $this->other_allowances ?: [],
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'working_time' => $this->working_time,
            'work_location' => $this->work_location,
            'note' => $this->note,
            'generated_pdf_path'  => $this->generated_pdf_path,
            'generated_pdf_url'   => $this->generated_pdf_path ? Storage::url($this->generated_pdf_path) : null,
            'created_at' => optional($this->created_at)->toDateTimeString(),

            // Attachments
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                        'mime_type' => $attachment->mime_type,
                        'created_at' => $attachment->created_at->toDateTimeString(),
                        'download_url' => route('contracts.appendixes.attachments.download', $attachment),
                    ];
                });
            }),
        ];
    }

    private function getAppendixTypeLabel(): ?string
    {
        return $this->appendix_type?->label();
    }

    private function getStatusLabel(): ?string
    {
        return match ($this->status) {
            'DRAFT' => 'Nháp',
            'ACTIVE' => 'Đang hiệu lực',
            'PENDING_APPROVAL' => 'Chờ duyệt',
            'EXPIRED' => 'Hết hiệu lực',
            'TERMINATED' => 'Đã chấm dứt',
            'CANCELLED' => 'Đã hủy',
            'REJECTED' => 'Đã từ chối',
            default => null,
        };
    }
}
