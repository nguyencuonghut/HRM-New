<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceMonthlyReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'month' => $this->month,
            'title' => $this->getTitle(),

            // Counters
            'total_increase' => $this->total_increase,
            'total_decrease' => $this->total_decrease,
            'total_adjust' => $this->total_adjust,
            'approved_increase' => $this->approved_increase,
            'approved_decrease' => $this->approved_decrease,
            'approved_adjust' => $this->approved_adjust,

            'total_insurance_salary' => $this->total_insurance_salary,

            // Export info
            'export_file_path' => $this->export_file_path,
            'exported_at' => $this->exported_at?->format('d/m/Y H:i'),
            'exported_by' => $this->whenLoaded('exportedBy', fn() => [
                'id' => $this->exportedBy->id,
                'name' => $this->exportedBy->name,
            ]),

            // Status
            'status' => $this->status,
            'is_finalized' => $this->isFinalized(),
            'finalized_at' => $this->finalized_at?->format('d/m/Y H:i'),
            'finalized_by' => $this->whenLoaded('finalizedBy', fn() => [
                'id' => $this->finalizedBy->id,
                'name' => $this->finalizedBy->name,
            ]),

            'notes' => $this->notes,

            // Progress
            'pending_count' => $this->whenCounted('pendingRecords'),
            'all_approved' => $this->allRecordsApproved(),

            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
