<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: InsuranceSalaryGradeConfig
 *
 * Thang hệ số lương BHXH (7 bậc) trong config set.
 * Mỗi config set PHẢI có đủ 7 bậc (1-7).
 *
 * Công thức: Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
 *
 * @property int $id
 * @property int $config_set_id
 * @property int $grade (1-7)
 * @property string $name
 * @property float $coefficient
 * @property string|null $description
 * @property bool $is_active
 *
 * Relationships:
 * @property-read \App\Models\InsuranceConfigSet $configSet
 */
class InsuranceSalaryGradeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_set_id',
        'grade',
        'name',
        'coefficient',
        'description',
        'is_active',
    ];

    protected $casts = [
        'grade' => 'integer',
        'coefficient' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Config set
     */
    public function configSet(): BelongsTo
    {
        return $this->belongsTo(InsuranceConfigSet::class, 'config_set_id');
    }

    /**
     * Scope: Active grades
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Tính lương BHXH với mức lương tối thiểu vùng
     *
     * @param float $minimumWage
     * @return float
     */
    public function calculateSalary(float $minimumWage): float
    {
        return $minimumWage * $this->coefficient;
    }

    /**
     * Format coefficient
     */
    public function getFormattedCoefficientAttribute(): string
    {
        return number_format($this->coefficient, 2, '.', '');
    }
}
