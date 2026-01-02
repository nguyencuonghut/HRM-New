<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeKpiMonth extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'employee_kpi_months';

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'kpi_score',
        'note',
        'input_by',
        'input_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'kpi_score' => 'decimal:2',
        'input_at' => 'datetime',
    ];

    /**
     * Relationship: Nhân viên
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relationship: Người nhập
     */
    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'year', 'month', 'kpi_score', 'note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Filter by year
     */
    public function scopeYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Filter by month
     */
    public function scopeMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope: Filter by employee
     */
    public function scopeEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
