<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Insurance Monthly Contribution
 *
 * Purpose: Snapshot of contribution per employee when report is FINALIZED
 * Prevents data changes after approval
 */
class InsuranceMonthlyContribution extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'employee_id',
        'change_record_id',
        'base_insurance_salary',
        'total_amount',
    ];

    protected $casts = [
        'base_insurance_salary' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the report that owns this contribution
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(InsuranceMonthlyReport::class, 'report_id');
    }

    /**
     * Get the employee for this contribution
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the change record that triggered this contribution
     */
    public function changeRecord(): BelongsTo
    {
        return $this->belongsTo(InsuranceChangeRecord::class, 'change_record_id');
    }

    /**
     * Get contribution items (breakdown by component)
     */
    public function items(): HasMany
    {
        return $this->hasMany(InsuranceMonthlyContributionItem::class, 'contribution_id');
    }

    /**
     * Get total amount formatted
     */
    public function getTotalAmountFormattedAttribute(): string
    {
        return number_format($this->total_amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get base salary formatted
     */
    public function getBaseSalaryFormattedAttribute(): string
    {
        return number_format($this->base_insurance_salary, 0, ',', '.') . ' VNĐ';
    }
}
