<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'submitted_at',
        'approved_at',
        'cancelled_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave-request')
            ->logOnly(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function employeeAbsence()
    {
        return $this->hasOne(\App\Models\EmployeeAbsence::class, 'leave_request_id');
    }

    /**
     * Business Logic Methods
     */

    /**
     * Calculate working days between start and end date
     * Excludes weekends (Saturday, Sunday)
     */
    public function calculateDays(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            // Skip weekends
            if (!$current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get remaining leave balance for this leave type and year
     */
    public function getRemainingDays(): float
    {
        $year = $this->start_date ? Carbon::parse($this->start_date)->year : now()->year;

        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $year)
            ->first();

        return $balance ? $balance->remaining_days : 0;
    }

    /**
     * Check if current user can approve this request
     *
     * @param int $userId Current user ID
     */
    public function canApprove(int $userId): bool
    {
        // Can't approve if not pending
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // Get current approved step (last step that was approved)
        $currentApprovedStep = $this->approvals()
            ->where('status', LeaveApproval::STATUS_APPROVED)
            ->max('step') ?? 0;

        $nextStep = $currentApprovedStep + 1;

        // Check if user is the next approver
        $nextApproval = $this->approvals()
            ->where('step', $nextStep)
            ->where('approver_id', $userId)
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->first();

        return $nextApproval !== null;
    }

    /**
     * Get the next approver in the approval chain
     */
    public function getNextApprover(): ?LeaveApproval
    {
        $currentStep = $this->approvals()->max('step') ?? 0;
        $nextStep = $currentStep + 1;

        return $this->approvals()
            ->where('step', $nextStep)
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->first();
    }

    /**
     * Check if all approvals are completed
     */
    public function isFullyApproved(): bool
    {
        $totalApprovals = $this->approvals()->count();
        $approvedCount = $this->approvals()
            ->where('status', LeaveApproval::STATUS_APPROVED)
            ->count();

        return $totalApprovals > 0 && $totalApprovals === $approvedCount;
    }

    /**
     * Check if any approval was rejected
     */
    public function isRejected(): bool
    {
        return $this->approvals()
            ->where('status', LeaveApproval::STATUS_REJECTED)
            ->exists();
    }

    /**
     * Check if this leave request overlaps with any existing leave requests
     *
     * @return LeaveRequest|null Returns the overlapping request if found
     */
    public function hasOverlappingLeave(): ?LeaveRequest
    {
        if (!$this->start_date || !$this->end_date || !$this->employee_id) {
            return null;
        }

        return LeaveRequest::where('employee_id', $this->employee_id)
            ->where('id', '!=', $this->id ?? '') // Exclude current request when updating
            ->whereIn('status', [
                self::STATUS_DRAFT,
                self::STATUS_PENDING,
                self::STATUS_APPROVED
            ])
            ->where(function($query) {
                $query->where(function($q) {
                    // New request's start is within existing range
                    $q->where('start_date', '<=', $this->start_date)
                      ->where('end_date', '>=', $this->start_date);
                })->orWhere(function($q) {
                    // New request's end is within existing range
                    $q->where('start_date', '<=', $this->end_date)
                      ->where('end_date', '>=', $this->end_date);
                })->orWhere(function($q) {
                    // New request encompasses existing range
                    $q->where('start_date', '>=', $this->start_date)
                      ->where('end_date', '<=', $this->end_date);
                });
            })
            ->with(['leaveType'])
            ->first();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
