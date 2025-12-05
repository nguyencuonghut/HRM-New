<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'remaining_days',
        'carried_forward',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carried_forward' => 'decimal:2',
    ];

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

    /**
     * Business Logic Methods
     */

    /**
     * Deduct days from balance
     */
    public function deductDays(float $days): void
    {
        $this->used_days += $days;
        $this->remaining_days = $this->total_days - $this->used_days;
        $this->save();
    }

    /**
     * Restore days to balance (e.g., when leave request is cancelled)
     */
    public function restoreDays(float $days): void
    {
        $this->used_days = max(0, $this->used_days - $days);
        $this->remaining_days = $this->total_days - $this->used_days;
        $this->save();
    }

    /**
     * Check if there are enough days available
     */
    public function hasEnoughDays(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    /**
     * Scopes
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForLeaveType($query, int $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }
}
