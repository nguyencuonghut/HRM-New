<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model: Company Region
 *
 * Purpose: Track company's BHXH region changes over time
 *
 * Business Rules:
 * - Region 1-4 (Vùng I, II, III, IV)
 * - Each region has different minimum wage
 * - INSERT new record when region changes (preserve history)
 * - Current region: effective_to = null
 * - No overlapping periods
 */
class CompanyRegion extends Model
{
    use HasUuids;

    protected $fillable = [
        'region',
        'effective_from',
        'effective_to',
        'note',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get the region name in Vietnamese
     */
    public function getRegionNameAttribute(): string
    {
        return match($this->region) {
            1 => 'Vùng I',
            2 => 'Vùng II',
            3 => 'Vùng III',
            4 => 'Vùng IV',
            default => 'N/A',
        };
    }

    /**
     * Check if this region is currently active
     */
    public function isActive(): bool
    {
        return $this->effective_to === null || $this->effective_to->isFuture();
    }

    /**
     * Scope: Get current active region
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('effective_to')
            ->orWhere('effective_to', '>', now());
    }

    /**
     * Scope: Get historical regions
     */
    public function scopeHistorical($query)
    {
        return $query->whereNotNull('effective_to')
            ->where('effective_to', '<=', now());
    }

    /**
     * Get the region effective at a specific date
     */
    public static function getRegionAtDate($date)
    {
        return static::where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}
