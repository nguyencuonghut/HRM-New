<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveApproval extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'step',
        'approver_role',
        'status',
        'comment',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'step' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    // Approver role constants
    public const ROLE_LINE_MANAGER = 'LINE_MANAGER';
    public const ROLE_DIRECTOR = 'DIRECTOR';
    public const ROLE_HR = 'HR';

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave-approval')
            ->logOnly(['leave_request_id', 'approver_id', 'step', 'status', 'comment'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationships
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForApprover($query, int $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByStep($query, int $step)
    {
        return $query->where('step', $step);
    }
}
