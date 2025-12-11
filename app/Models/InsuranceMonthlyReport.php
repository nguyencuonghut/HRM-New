<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceMonthlyReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'year',
        'month',
        'total_increase',
        'total_decrease',
        'total_adjust',
        'approved_increase',
        'approved_decrease',
        'approved_adjust',
        'total_insurance_salary',
        'export_file_path',
        'exported_at',
        'exported_by',
        'status',
        'finalized_at',
        'finalized_by',
        'notes',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'finalized_at' => 'datetime',
        'total_insurance_salary' => 'decimal:2',
    ];

    // Status constants
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_FINALIZED = 'FINALIZED';

    /**
     * Get all change records
     */
    public function changeRecords(): HasMany
    {
        return $this->hasMany(InsuranceChangeRecord::class, 'report_id');
    }

    /**
     * Get increase records
     */
    public function increaseRecords(): HasMany
    {
        return $this->hasMany(InsuranceChangeRecord::class, 'report_id')
            ->where('change_type', InsuranceChangeRecord::TYPE_INCREASE);
    }

    /**
     * Get decrease records
     */
    public function decreaseRecords(): HasMany
    {
        return $this->hasMany(InsuranceChangeRecord::class, 'report_id')
            ->where('change_type', InsuranceChangeRecord::TYPE_DECREASE);
    }

    /**
     * Get adjust records
     */
    public function adjustRecords(): HasMany
    {
        return $this->hasMany(InsuranceChangeRecord::class, 'report_id')
            ->where('change_type', InsuranceChangeRecord::TYPE_ADJUST);
    }

    /**
     * Get pending records (need approval)
     */
    public function pendingRecords(): HasMany
    {
        return $this->hasMany(InsuranceChangeRecord::class, 'report_id')
            ->where('approval_status', InsuranceChangeRecord::APPROVAL_PENDING);
    }

    /**
     * Get user who exported
     */
    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }

    /**
     * Get user who finalized
     */
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Scope: Draft reports
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Finalized reports
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    /**
     * Check if report is finalized
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Check if all records are approved (no pending)
     */
    public function allRecordsApproved(): bool
    {
        return $this->pendingRecords()->count() === 0;
    }

    /**
     * Get report title
     */
    public function getTitle(): string
    {
        return "Báo cáo tháng {$this->month}/{$this->year}";
    }
}
