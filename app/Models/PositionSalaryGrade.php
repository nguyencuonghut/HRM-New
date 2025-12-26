<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: PositionSalaryGrade
 *
 * Quản lý thang hệ số lương (7 bậc) cho mỗi vị trí/chức danh
 *
 * @property string $id
 * @property string $position_id
 * @property int $grade (1-7)
 * @property float $coefficient
 * @property \Carbon\Carbon $effective_from
 * @property \Carbon\Carbon|null $effective_to
 * @property bool $is_active
 * @property string|null $note
 *
 * Relationships:
 * @property-read \App\Models\Position $position
 */
class PositionSalaryGrade extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'position_id',
        'grade',
        'coefficient',
        'effective_from',
        'effective_to',
        'is_active',
        'note',
    ];

    protected $casts = [
        'grade' => 'integer',
        'coefficient' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Vị trí/chức danh
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Scope: Đang hiệu lực
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Tính lương BHXH cho bậc này với mức lương tối thiểu vùng
     *
     * @param float $minimumWage Lương tối thiểu vùng
     * @return float
     */
    public function calculateSalary(float $minimumWage): float
    {
        return $minimumWage * $this->coefficient;
    }

    /**
     * Lấy tất cả bậc cho 1 position tại thời điểm cụ thể
     *
     * @param string $positionId
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllGradesForPosition(string $positionId, string $date = null)
    {
        $query = static::where('position_id', $positionId)
                      ->where('is_active', true)
                      ->orderBy('grade');

        if ($date) {
            $query->effectiveAt($date);
        } else {
            $query->whereNull('effective_to');
        }

        return $query->get();
    }
}
