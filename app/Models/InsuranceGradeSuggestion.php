<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: InsuranceGradeSuggestion
 *
 * Quản lý đề xuất tăng bậc BHXH
 *
 * @property string $id
 * @property string $employee_id
 * @property int $current_grade
 * @property int $suggested_grade
 * @property float $tenure_years
 * @property string|null $reason
 * @property string $status
 * @property string|null $processed_by
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $process_note
 * @property string|null $created_appendix_id
 * @property \Carbon\Carbon $suggested_at
 * @property \Carbon\Carbon|null $expires_at
 *
 * Relationships:
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\User|null $processor
 * @property-read \App\Models\ContractAppendix|null $createdAppendix
 */
class InsuranceGradeSuggestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'current_grade',
        'suggested_grade',
        'tenure_years',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'process_note',
        'created_appendix_id',
        'suggested_at',
        'expires_at',
    ];

    protected $casts = [
        'current_grade' => 'integer',
        'suggested_grade' => 'integer',
        'tenure_years' => 'decimal:2',
        'processed_at' => 'datetime',
        'suggested_at' => 'date',
        'expires_at' => 'date',
    ];

    /**
     * Nhân viên
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Người xử lý (HR)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    /**
     * Phụ lục được tạo (nếu duyệt)
     */
    public function createdAppendix(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ContractAppendix::class, 'created_appendix_id');
    }

    /**
     * Scope: Đang chờ duyệt
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: Đã duyệt
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope: Từ chối
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    /**
     * Scope: Quá hạn (expires_at < today và status = PENDING)
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'PENDING')
                    ->where('expires_at', '<', now());
    }

    /**
     * Check xem có quá hạn không
     */
    public function isExpired(): bool
    {
        return $this->status === 'PENDING'
            && $this->expires_at
            && $this->expires_at->isPast();
    }

    /**
     * Approve suggestion
     */
    public function approve(string $appendixId, ?string $note = null)
    {
        $this->status = 'APPROVED';
        $this->processed_by = auth()->id();
        $this->processed_at = now();
        $this->process_note = $note;
        $this->created_appendix_id = $appendixId;
        $this->save();
    }

    /**
     * Reject suggestion
     */
    public function reject(?string $note = null)
    {
        $this->status = 'REJECTED';
        $this->processed_by = auth()->id();
        $this->processed_at = now();
        $this->process_note = $note;
        $this->save();
    }

    /**
     * Mark as expired
     */
    public function markExpired()
    {
        $this->status = 'EXPIRED';
        $this->save();
    }
}
