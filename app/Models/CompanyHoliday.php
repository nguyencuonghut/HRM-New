<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanyHoliday extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'name',
        'holiday_date',
        'year',
        'is_compensated',
        'compensated_date',
        'is_recurring',
        'note',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'compensated_date' => 'date',
        'is_compensated' => 'boolean',
        'is_recurring' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Get activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'holiday_date', 'year', 'is_compensated', 'compensated_date', 'is_recurring', 'note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope for current year holidays
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope for specific year
     */
    public function scopeYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope for date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('holiday_date', [$startDate, $endDate]);
    }

    /**
     * Check if holiday is in the past
     */
    public function isPast(): bool
    {
        return $this->holiday_date->isPast();
    }

    /**
     * Check if holiday is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->holiday_date->isFuture();
    }

    /**
     * Get days until holiday
     */
    public function daysUntil(): int
    {
        return now()->diffInDays($this->holiday_date, false);
    }
}
