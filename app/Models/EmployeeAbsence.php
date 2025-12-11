<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAbsence extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'absence_type',
        'start_date',
        'end_date',
        'duration_days',
        'affects_insurance',
        'reason',
        'leave_request_id',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'affects_insurance' => 'boolean',
    ];

    // Absence type constants
    const TYPE_MATERNITY = 'MATERNITY';
    const TYPE_SICK_LONG = 'SICK_LONG';
    const TYPE_UNPAID_LONG = 'UNPAID_LONG';
    const TYPE_MILITARY = 'MILITARY';
    const TYPE_STUDY = 'STUDY';
    const TYPE_OTHER = 'OTHER';

    // Status constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_ENDED = 'ENDED';

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the related leave request
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Scope: Active absences
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Affects insurance (>30 days)
     */
    public function scopeAffectsInsurance($query)
    {
        return $query->where('affects_insurance', true);
    }

    /**
     * Check if absence is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get absence type label (Vietnamese)
     */
    public function getTypeLabel(): string
    {
        return match($this->absence_type) {
            self::TYPE_MATERNITY => 'Thai sản',
            self::TYPE_SICK_LONG => 'Ốm dài hạn',
            self::TYPE_UNPAID_LONG => 'Nghỉ không lương dài hạn',
            self::TYPE_MILITARY => 'Nghĩa vụ quân sự',
            self::TYPE_STUDY => 'Học tập',
            default => 'Khác',
        };
    }
}
