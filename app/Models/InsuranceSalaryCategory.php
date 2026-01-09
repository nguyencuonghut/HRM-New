<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model: InsuranceSalaryCategory
 *
 * Purpose: Standardized insurance salary categories for positions
 * - Prevents data inconsistencies
 * - Provides dropdown options in UI
 * - Enables proper grouping and reporting
 *
 * Relationships:
 * - Has many positions
 */
class InsuranceSalaryCategory extends Model
{
    use HasUuids;

    protected $table = 'insurance_salary_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all positions in this category
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Get positions count
     */
    public function getPositionsCountAttribute()
    {
        return $this->positions()->count();
    }
}
