<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payroll_item_id',
        'type',
        'amount',
        'reason',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Type constants
    public const TYPE_BONUS = 'BONUS';
    public const TYPE_PENALTY = 'PENALTY';
    public const TYPE_ADVANCE = 'ADVANCE';
    public const TYPE_OVERTIME = 'OVERTIME';
    public const TYPE_OTHER = 'OTHER';

    /**
     * Relationships
     */
    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeBonus($query)
    {
        return $query->where('type', self::TYPE_BONUS);
    }

    public function scopePenalty($query)
    {
        return $query->where('type', self::TYPE_PENALTY);
    }

    public function scopeOvertime($query)
    {
        return $query->where('type', self::TYPE_OVERTIME);
    }
}
