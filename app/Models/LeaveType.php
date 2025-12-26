<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveType extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'color',
        'days_per_year',
        'requires_approval',
        'is_paid',
        'is_active',
        'order_index',
        'description',
    ];

    protected $casts = [
        'days_per_year' => 'integer',
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    protected $appends = ['code_label'];

    /**
     * Get the Vietnamese label for the leave type code
     */
    public function getCodeLabelAttribute(): string
    {
        return match($this->code) {
            'ANNUAL' => 'Phép năm',
            'SICK' => 'Ốm đau',
            'PERSONAL_PAID' => 'Việc riêng',
            'UNPAID' => 'Không lương',
            'MATERNITY' => 'Thai sản',
            'STUDY' => 'Học tập',
            'BUSINESS' => 'Công tác',
            'BEREAVEMENT' => 'Tang lễ',
            'MARRIAGE' => 'Kết hôn',
            'COMPENSATORY' => 'Bù',
            'OTHERS' => 'Khác',
            default => $this->code,
        };
    }

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave-type')
            ->logOnly(['name', 'code', 'color', 'days_per_year', 'requires_approval', 'is_paid', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationships
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index')->orderBy('name');
    }
}
