<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: Insurance Participation Component
 *
 * Purpose: Detail table for each participation's components
 * Allows customization per employee:
 * - Enable/disable specific components
 * - Override rate
 * - Use FIXED_AMOUNT for special cases (BHTN 72M)
 */
class InsuranceParticipationComponent extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_participation_id',
        'component_id',
        'is_enabled',
        'rate_total',
        'base_type',
        'base_amount',
        'note',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'rate_total' => 'decimal:5',
        'base_amount' => 'decimal:2',
    ];

    /**
     * Get the participation that owns this component
     */
    public function participation(): BelongsTo
    {
        return $this->belongsTo(InsuranceParticipation::class, 'insurance_participation_id');
    }

    /**
     * Get the component definition
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(InsuranceComponent::class, 'component_id');
    }

    /**
     * Scope: Only enabled components
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Check if using fixed amount base
     */
    public function isFixedAmount(): bool
    {
        return $this->base_type === 'FIXED_AMOUNT';
    }

    /**
     * Get rate as percentage string
     */
    public function getRatePercentageAttribute(): string
    {
        return number_format($this->rate_total * 100, 2) . '%';
    }
}
