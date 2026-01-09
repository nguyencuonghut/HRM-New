<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: InsuranceMinimumWageConfig
 *
 * Lương tối thiểu vùng trong config set.
 * Mỗi config set PHẢI có đủ 4 vùng (1-4).
 *
 * @property int $id
 * @property int $config_set_id
 * @property int $region (1-4)
 * @property float $amount
 * @property \Carbon\Carbon|null $effective_from
 * @property \Carbon\Carbon|null $effective_to
 * @property string|null $note
 *
 * Relationships:
 * @property-read \App\Models\InsuranceConfigSet $configSet
 */
class InsuranceMinimumWageConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_set_id',
        'region',
        'amount',
        'effective_from',
        'effective_to',
        'note',
    ];

    protected $casts = [
        'region' => 'integer',
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Relationship: Config set
     */
    public function configSet(): BelongsTo
    {
        return $this->belongsTo(InsuranceConfigSet::class, 'config_set_id');
    }

    /**
     * Lấy tên vùng
     */
    public function getRegionNameAttribute(): string
    {
        return match($this->region) {
            1 => 'Vùng I',
            2 => 'Vùng II',
            3 => 'Vùng III',
            4 => 'Vùng IV',
            default => "Vùng {$this->region}",
        };
    }

    /**
     * Format amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }
}
