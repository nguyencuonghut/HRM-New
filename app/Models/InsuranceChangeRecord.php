<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceChangeRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'report_id',
        'employee_id',
        'change_type',
        'insurance_salary',
        'has_social_insurance',
        'has_health_insurance',
        'has_unemployment_insurance',
        'auto_reason',
        'system_notes',
        'effective_date',
        'contract_id',
        'contract_appendix_id',
        'leave_request_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'admin_notes',
        'adjusted_salary',
        'adjustment_reason',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'approved_at' => 'datetime',
        'insurance_salary' => 'decimal:2',
        'adjusted_salary' => 'decimal:2',
        'has_social_insurance' => 'boolean',
        'has_health_insurance' => 'boolean',
        'has_unemployment_insurance' => 'boolean',
    ];

    // Change type constants
    const TYPE_INCREASE = 'INCREASE';
    const TYPE_DECREASE = 'DECREASE';
    const TYPE_ADJUST = 'ADJUST';

    // Auto reason constants
    const REASON_NEW_HIRE = 'NEW_HIRE';
    const REASON_TERMINATION = 'TERMINATION';
    const REASON_LONG_ABSENCE = 'LONG_ABSENCE';
    const REASON_SALARY_CHANGE = 'SALARY_CHANGE';
    const REASON_RETURN_TO_WORK = 'RETURN_TO_WORK';
    const REASON_OTHER = 'OTHER';

    // Approval status constants
    const APPROVAL_PENDING = 'PENDING';
    const APPROVAL_APPROVED = 'APPROVED';
    const APPROVAL_REJECTED = 'REJECTED';
    const APPROVAL_ADJUSTED = 'ADJUSTED';

    /**
     * Get the report
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(InsuranceMonthlyReport::class, 'report_id');
    }

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the contract
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the contract appendix
     */
    public function contractAppendix(): BelongsTo
    {
        return $this->belongsTo(ContractAppendix::class);
    }

    /**
     * Get the leave request
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Get user who approved
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Pending approval
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope: Approved
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    /**
     * Scope: For export (approved + adjusted)
     */
    public function scopeForExport($query)
    {
        return $query->whereIn('approval_status', [self::APPROVAL_APPROVED, self::APPROVAL_ADJUSTED]);
    }

    /**
     * Scope: By change type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Check if record is pending
     */
    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check if record is approved
     */
    public function isApproved(): bool
    {
        return in_array($this->approval_status, [self::APPROVAL_APPROVED, self::APPROVAL_ADJUSTED]);
    }

    /**
     * Get final salary (adjusted or original)
     */
    public function getFinalSalary(): float
    {
        return $this->adjusted_salary ?? $this->insurance_salary;
    }

    /**
     * Get auto reason label (Vietnamese)
     */
    public function getAutoReasonLabel(): string
    {
        return match($this->auto_reason) {
            self::REASON_NEW_HIRE => 'Nhân viên mới',
            self::REASON_TERMINATION => 'Nghỉ việc',
            self::REASON_LONG_ABSENCE => 'Nghỉ dài hạn',
            self::REASON_SALARY_CHANGE => 'Thay đổi lương',
            self::REASON_RETURN_TO_WORK => 'Quay lại làm việc',
            default => 'Khác',
        };
    }
}
