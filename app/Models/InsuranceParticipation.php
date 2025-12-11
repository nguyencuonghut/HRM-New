<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceParticipation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'participation_start_date',
        'participation_end_date',
        'has_social_insurance',
        'has_health_insurance',
        'has_unemployment_insurance',
        'insurance_salary',
        'contract_id',
        'contract_appendix_id',
        'status',
    ];

    protected $casts = [
        'participation_start_date' => 'date',
        'participation_end_date' => 'date',
        'has_social_insurance' => 'boolean',
        'has_health_insurance' => 'boolean',
        'has_unemployment_insurance' => 'boolean',
        'insurance_salary' => 'decimal:2',
    ];

    // Status constants
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_SUSPENDED = 'SUSPENDED';
    const STATUS_TERMINATED = 'TERMINATED';

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the contract
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the contract appendix
     */
    public function contractAppendix(): BelongsTo
    {
        return $this->belongsTo(ContractAppendix::class);
    }

    /**
     * Scope: Active participations
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Current (not ended)
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('participation_end_date')
            ->orWhere('participation_end_date', '>', now());
    }

    /**
     * Check if participation is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->participation_end_date === null || $this->participation_end_date->isFuture());
    }
}
