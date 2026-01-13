<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Insurance Component
 *
 * Purpose: Master table for 5 insurance components
 * - RETIREMENT_SURVIVOR: Hưu trí và tử tuất (22%)
 * - SICKNESS_MATERNITY: Ốm đau và thai sản (3%)
 * - OCC_ACCIDENT_DISEASE: TNLĐ-BNN (0.5%)
 * - UNEMPLOYMENT: Bảo hiểm thất nghiệp (2%)
 * - HEALTH: Bảo hiểm y tế (4.5%)
 */
class InsuranceComponent extends Model
{
    protected $fillable = [
        'code',
        'name_vi',
        'default_rate_total',
        'is_active',
    ];

    protected $casts = [
        'default_rate_total' => 'decimal:5',
        'is_active' => 'boolean',
    ];

    /**
     * Get participation components using this component
     */
    public function participationComponents(): HasMany
    {
        return $this->hasMany(InsuranceParticipationComponent::class, 'component_id');
    }

    /**
     * Get contribution items using this component
     */
    public function contributionItems(): HasMany
    {
        return $this->hasMany(InsuranceMonthlyContributionItem::class, 'component_id');
    }

    /**
     * Scope: Only active components
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get rate as percentage string
     */
    public function getRatePercentageAttribute(): string
    {
        return number_format($this->default_rate_total * 100, 2) . '%';
    }
}
