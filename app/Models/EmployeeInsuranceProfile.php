<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: EmployeeInsuranceProfile
 *
 * Quản lý hồ sơ BHXH của nhân viên (bậc lương BHXH hiện tại + lịch sử)
 *
 * @property string $id
 * @property string $employee_id
 * @property string|null $position_id
 * @property int $grade (1-7)
 * @property \Carbon\Carbon $applied_from
 * @property \Carbon\Carbon|null $applied_to
 * @property string|null $reason
 * @property string|null $source_appendix_id
 * @property string|null $note
 * @property string|null $created_by
 *
 * Relationships:
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\Position|null $position
 * @property-read \App\Models\ContractAppendix|null $sourceAppendix
 */
class EmployeeInsuranceProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'position_id',
        'grade',
        'applied_from',
        'applied_to',
        'reason',
        'source_appendix_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'grade' => 'integer',
        'applied_from' => 'date',
        'applied_to' => 'date',
    ];

    /**
     * Nhân viên sở hữu hồ sơ này
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Vị trí áp dụng
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Phụ lục HĐLĐ làm căn cứ pháp lý (nếu có)
     */
    public function sourceAppendix(): BelongsTo
    {
        return $this->belongsTo(ContractAppendix::class, 'source_appendix_id');
    }

    /**
     * Scope: Hồ sơ đang áp dụng (applied_to = NULL)
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('applied_to');
    }

    /**
     * Scope: Hồ sơ tại thời điểm cụ thể
     */
    public function scopeAtDate($query, $date)
    {
        return $query->where('applied_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('applied_to')
                          ->orWhere('applied_to', '>=', $date);
                    });
    }

    /**
     * Tính lương BHXH tại thời điểm này
     *
     * @param int $region Vùng lương tối thiểu (1-4)
     * @return float|null
     */
    public function calculateInsuranceSalary(int $region): ?float
    {
        if (!$this->position_id) {
            return null;
        }

        // Lấy hệ số bậc tại thời điểm applied_from
        $gradeData = PositionSalaryGrade::where('position_id', $this->position_id)
            ->where('grade', $this->grade)
            ->where('effective_from', '<=', $this->applied_from)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $this->applied_from);
            })
            ->where('is_active', true)
            ->first();

        if (!$gradeData) {
            return null;
        }

        // Lấy lương tối thiểu vùng tại thời điểm applied_from
        $minWage = MinimumWage::where('region', $region)
            ->where('effective_from', '<=', $this->applied_from)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $this->applied_from);
            })
            ->where('is_active', true)
            ->first();

        if (!$minWage) {
            return null;
        }

        // Tính: Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
        return $minWage->amount * $gradeData->coefficient;
    }
}
