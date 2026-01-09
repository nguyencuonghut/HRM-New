<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: InsuranceConfigSet
 *
 * Bộ cấu hình hệ thống lương BHXH với versioning.
 * Một config set chứa:
 * - 4 mức lương tối thiểu vùng (regions 1-4)
 * - 7 bậc hệ số lương (grades 1-7)
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $status (DRAFT, ACTIVE, ARCHIVED)
 * @property \Carbon\Carbon $effective_from
 * @property \Carbon\Carbon|null $effective_to
 * @property int|null $based_on_set_id
 * @property int|null $created_by
 * @property int|null $activated_by
 * @property \Carbon\Carbon|null $activated_at
 * @property int|null $archived_by
 * @property \Carbon\Carbon|null $archived_at
 * @property string|null $notes
 *
 * Relationships:
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InsuranceMinimumWageConfig[] $minimumWages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InsuranceSalaryGradeConfig[] $salaryGrades
 * @property-read \App\Models\InsuranceConfigSet|null $basedOnSet
 */
class InsuranceConfigSet extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_ARCHIVED = 'ARCHIVED';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'effective_from',
        'effective_to',
        'based_on_set_id',
        'created_by',
        'activated_by',
        'activated_at',
        'archived_by',
        'archived_at',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'activated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Relationship: Minimum wages trong config set này
     */
    public function minimumWages(): HasMany
    {
        return $this->hasMany(InsuranceMinimumWageConfig::class, 'config_set_id')
                    ->orderBy('region');
    }

    /**
     * Relationship: Salary grades trong config set này
     */
    public function salaryGrades(): HasMany
    {
        return $this->hasMany(InsuranceSalaryGradeConfig::class, 'config_set_id')
                    ->where('is_active', true)
                    ->orderBy('grade');
    }

    /**
     * Relationship: Config set gốc (nếu clone)
     */
    public function basedOnSet(): BelongsTo
    {
        return $this->belongsTo(InsuranceConfigSet::class, 'based_on_set_id');
    }

    /**
     * Scope: Draft sets
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Active sets
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Archived sets
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope: Hiệu lực tại thời điểm cụ thể
     */
    public function scopeEffectiveAt($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $date);
                    });
    }

    /**
     * Kiểm tra xem có phải DRAFT không
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Kiểm tra xem có phải ACTIVE không
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Kiểm tra xem có phải ARCHIVED không
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Kiểm tra xem config set có đủ 4 vùng không
     */
    public function hasAllRegions(): bool
    {
        return $this->minimumWages()->count() === 4;
    }

    /**
     * Kiểm tra xem config set có đủ 7 bậc không
     */
    public function hasAllGrades(): bool
    {
        return $this->salaryGrades()->count() === 7;
    }

    /**
     * Validate config set trước khi activate
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForActivation(): array
    {
        $errors = [];

        // Kiểm tra đủ 4 vùng
        if (!$this->hasAllRegions()) {
            $regions = $this->minimumWages()->pluck('region')->toArray();
            $missing = array_diff([1, 2, 3, 4], $regions);
            $errors[] = 'Thiếu lương tối thiểu cho vùng: ' . implode(', ', $missing);
        }

        // Kiểm tra đủ 7 bậc
        if (!$this->hasAllGrades()) {
            $grades = $this->salaryGrades()->pluck('grade')->toArray();
            $missing = array_diff([1, 2, 3, 4, 5, 6, 7], $grades);
            $errors[] = 'Thiếu hệ số cho bậc: ' . implode(', ', $missing);
        }

        // Kiểm tra coefficient > 0
        $invalidCoefficients = $this->salaryGrades()
            ->where('coefficient', '<=', 0)
            ->get();

        if ($invalidCoefficients->isNotEmpty()) {
            foreach ($invalidCoefficients as $grade) {
                $errors[] = "Hệ số bậc {$grade->grade} phải lớn hơn 0";
            }
        }

        // Kiểm tra không overlap với ACTIVE set khác
        $overlapping = static::active()
            ->where('id', '!=', $this->id)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // effective_from của set này nằm trong khoảng của set khác
                    $q2->where('effective_from', '<=', $this->effective_from)
                       ->where(function ($q3) {
                           $q3->whereNull('effective_to')
                              ->orWhere('effective_to', '>=', $this->effective_from);
                       });
                })
                ->orWhere(function ($q2) {
                    // effective_to của set này nằm trong khoảng của set khác (nếu có)
                    if ($this->effective_to) {
                        $q2->where('effective_from', '<=', $this->effective_to)
                           ->where(function ($q3) {
                               $q3->whereNull('effective_to')
                                  ->orWhere('effective_to', '>=', $this->effective_to);
                           });
                    }
                });
            })
            ->exists();

        if ($overlapping) {
            $errors[] = 'Khoảng thời gian hiệu lực bị trùng với config set ACTIVE khác';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Format status để hiển thị
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_ACTIVE => 'Đang áp dụng',
            self::STATUS_ARCHIVED => 'Đã lưu trữ',
            default => $this->status,
        };
    }

    /**
     * Format effective period
     */
    public function getEffectivePeriodAttribute(): string
    {
        $from = $this->effective_from->format('d/m/Y');
        $to = $this->effective_to ? $this->effective_to->format('d/m/Y') : 'Vô thời hạn';

        return "{$from} - {$to}";
    }
}
