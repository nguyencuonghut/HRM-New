<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model: MinimumWage
 *
 * Quản lý lương tối thiểu vùng theo thời gian
 * (Nghị định của Chính phủ về mức lương tối thiểu vùng)
 *
 * @property string $id
 * @property int $region (1-4)
 * @property int $amount (VND)
 * @property \Carbon\Carbon $effective_from
 * @property \Carbon\Carbon|null $effective_to
 * @property bool $is_active
 * @property string|null $note
 *
 * Regions:
 * 1 = Vùng I (thành phố lớn như Hà Nội, TP.HCM)
 * 2 = Vùng II
 * 3 = Vùng III
 * 4 = Vùng IV (vùng sâu, vùng xa)
 */
class MinimumWage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'region',
        'amount',
        'effective_from',
        'effective_to',
        'is_active',
        'note',
    ];

    protected $casts = [
        'region' => 'integer',
        'amount' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

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
     * Lấy lương tối thiểu vùng tại thời điểm cụ thể
     *
     * @param int $region Vùng (1-4)
     * @param string|null $date Ngày (null = hôm nay)
     * @return MinimumWage|null
     */
    public static function getForRegion(int $region, ?string $date = null): ?MinimumWage
    {
        $date = $date ?? now()->format('Y-m-d');

        return static::where('region', $region)
                    ->active()
                    ->effectiveAt($date)
                    ->orderBy('effective_from', 'desc')
                    ->first();
    }

    /**
     * Lấy tất cả mức lương tối thiểu hiện tại (4 vùng)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllCurrent()
    {
        return static::active()
                    ->whereNull('effective_to')
                    ->orderBy('region')
                    ->get();
    }

    /**
     * Format amount có dấu phân cách
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Tên vùng
     *
     * @return string
     */
    public function getRegionNameAttribute(): string
    {
        $names = [
            1 => 'Vùng I',
            2 => 'Vùng II',
            3 => 'Vùng III',
            4 => 'Vùng IV',
        ];

        return $names[$this->region] ?? 'Không xác định';
    }
}
