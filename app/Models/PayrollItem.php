<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollItem extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'contract_id',
        'base_salary',
        'assignment_id',
        'department_name',
        'position_title',
        'role_type',
        'position_allowance',
        'responsibility_allowance',
        'other_allowances',
        'total_allowances',
        'social_insurance',
        'health_insurance',
        'unemployment_insurance',
        'income_tax',
        'other_deductions',
        'total_deductions',
        'gross_salary',
        'net_salary',
        'working_days',
        'standard_days',
        'attendance_rate',
        'note',
        'calculation_details',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'position_allowance' => 'decimal:2',
        'responsibility_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'social_insurance' => 'decimal:2',
        'health_insurance' => 'decimal:2',
        'unemployment_insurance' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'working_days' => 'integer',
        'standard_days' => 'integer',
        'attendance_rate' => 'decimal:2',
        'calculation_details' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll-item')
            ->logOnly(['employee_id', 'base_salary', 'gross_salary', 'net_salary'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationships
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssignment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    /**
     * Scopes
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('payroll_period_id', $periodId);
    }

    /**
     * Helper Methods
     */
    public function getTotalAdjustments(): float
    {
        return $this->adjustments->sum(function ($adjustment) {
            return $adjustment->type === 'PENALTY' ? -abs($adjustment->amount) : abs($adjustment->amount);
        });
    }

    public function getFinalNetSalary(): float
    {
        return $this->net_salary + $this->getTotalAdjustments();
    }
}
