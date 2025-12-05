<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
            ] : null,
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => $this->leaveType ? [
                'id' => $this->leaveType->id,
                'name' => $this->leaveType->name,
                'code' => $this->leaveType->code,
                'color' => $this->leaveType->color,
                'is_paid' => $this->leaveType->is_paid,
            ] : null,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days' => (float) $this->days,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'note' => $this->note,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Approvals
            'approvals' => $this->whenLoaded('approvals', function () {
                return $this->approvals->map(function ($approval) {
                    return [
                        'id' => $approval->id,
                        'step' => $approval->step,
                        'approver_role' => $approval->approver_role,
                        'approver' => $approval->approver ? [
                            'id' => $approval->approver->id,
                            'name' => $approval->approver->name,
                        ] : null,
                        'status' => $approval->status,
                        'status_label' => $this->getApprovalStatusLabel($approval->status),
                        'comment' => $approval->comment,
                        'approved_at' => $approval->approved_at?->format('Y-m-d H:i:s'),
                        'rejected_at' => $approval->rejected_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),

            // Computed
            'remaining_days' => $this->when(
                $this->relationLoaded('employee') && $this->relationLoaded('leaveType'),
                fn() => $this->getRemainingDays()
            ),
            'can_edit' => in_array($this->status, ['DRAFT']),
            'can_cancel' => in_array($this->status, ['DRAFT', 'PENDING']),
            'can_delete' => in_array($this->status, ['DRAFT', 'CANCELLED']),
        ];
    }

    /**
     * Get status label in Vietnamese
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'DRAFT' => 'Nháp',
            'PENDING' => 'Chờ duyệt',
            'APPROVED' => 'Đã duyệt',
            'REJECTED' => 'Từ chối',
            'CANCELLED' => 'Đã hủy',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    protected function getStatusColor(): string
    {
        return match ($this->status) {
            'DRAFT' => 'secondary',
            'PENDING' => 'warn',
            'APPROVED' => 'success',
            'REJECTED' => 'danger',
            'CANCELLED' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get approval status label
     */
    protected function getApprovalStatusLabel(string $status): string
    {
        return match ($status) {
            'PENDING' => 'Chờ duyệt',
            'APPROVED' => 'Đã duyệt',
            'REJECTED' => 'Từ chối',
            default => $status,
        };
    }
}
