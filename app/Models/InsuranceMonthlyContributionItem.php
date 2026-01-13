<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: Insurance Monthly Contribution Item
 *
 * Purpose: Breakdown of contribution by component
 * Snapshot detail showing how total_amount is calculated
 */
class InsuranceMonthlyContributionItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'contribution_id',
        'component_id',
        'component_code',
        'component_name',
        'base_type',
        'base_used',
        'rate_total',
        'amount',
    ];

    protected $casts = [
        'base_used' => 'decimal:2',
        'rate_total' => 'decimal:5',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the contribution that owns this item
     */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(InsuranceMonthlyContribution::class, 'contribution_id');
    }

    /**
     * Get the component definition
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(InsuranceComponent::class, 'component_id');
    }

    /**
     * Get amount formatted
     */
    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get base used formatted
     */
    public function getBaseUsedFormattedAttribute(): string
    {
        return number_format($this->base_used, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get rate as percentage string
     */
    public function getRatePercentageAttribute(): string
    {
        return number_format($this->rate_total * 100, 2) . '%';
    }
}
