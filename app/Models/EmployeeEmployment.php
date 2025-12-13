<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeEmployment extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'end_reason',
        'is_current',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'employment_id');
    }

    /**
     * Scopes
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeActive($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    /**
     * Calculate duration in days
     */
    public function getDurationInDays(): int
    {
        $end = $this->end_date ?? now();
        return $this->start_date->diffInDays($end);
    }

    /**
     * Calculate duration in years (for seniority)
     */
    public function getDurationInYears(): int
    {
        $end = $this->end_date ?? now();
        return $this->start_date->diffInYears($end);
    }

    /**
     * Calculate duration in months
     */
    public function getDurationInMonths(): int
    {
        $end = $this->end_date ?? now();
        return $this->start_date->diffInMonths($end);
    }

    /**
     * Get formatted duration (years, months, days)
     * Example: ['years' => 2, 'months' => 3, 'days' => 12]
     */
    public function getFormattedDuration(): array
    {
        $end = $this->end_date ?? now();
        $diff = $this->start_date->diff($end);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'total_days' => $this->getDurationInDays(),
        ];
    }

    /**
     * Get human-readable duration
     * Example: "2 năm 3 tháng 12 ngày"
     */
    public function getHumanDuration(): string
    {
        $duration = $this->getFormattedDuration();
        $parts = [];

        if ($duration['years'] > 0) {
            $parts[] = $duration['years'] . ' năm';
        }
        if ($duration['months'] > 0) {
            $parts[] = $duration['months'] . ' tháng';
        }
        if ($duration['days'] > 0 || empty($parts)) {
            $parts[] = $duration['days'] . ' ngày';
        }

        return implode(' ', $parts);
    }

    /**
     * Check if employment was active during a specific period
     */
    public function wasActiveDuring(Carbon $startDate, Carbon $endDate): bool
    {
        // Employment starts before or during the period
        $startsInRange = $this->start_date->lte($endDate);

        // Employment ends after or during the period (or is still active)
        $endsInRange = is_null($this->end_date) || $this->end_date->gte($startDate);

        return $startsInRange && $endsInRange;
    }

    /**
     * End this employment period
     */
    public function endEmployment(Carbon $endDate, string $reason, ?string $note = null): void
    {
        $this->update([
            'end_date' => $endDate,
            'end_reason' => $reason,
            'is_current' => false,
            'note' => $note ?? $this->note,
        ]);
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'start_date', 'end_date', 'end_reason', 'is_current', 'note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employee-employment');
    }

    /**
     * Get description for activity log
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Tạo chu kỳ làm việc',
            'updated' => 'Cập nhật chu kỳ làm việc',
            'deleted' => 'Xóa chu kỳ làm việc',
            default => $eventName,
        };
    }
}
