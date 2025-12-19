<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\RewardDisciplineType;
use App\Enums\RewardDisciplineCategory;
use App\Enums\RewardDisciplineStatus;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeRewardDiscipline extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'employee_id',
        'type',
        'category',
        'decision_no',
        'decision_date',
        'effective_date',
        'amount',
        'description',
        'note',
        'evidence_files',
        'issued_by',
        'status',
        'related_contract_id',
    ];

    protected $casts = [
        'type' => RewardDisciplineType::class,
        'category' => RewardDisciplineCategory::class,
        'status' => RewardDisciplineStatus::class,
        'decision_date' => 'date',
        'effective_date' => 'date',
        'amount' => 'decimal:2',
        'evidence_files' => 'array',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'related_contract_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_by');
    }

    // Scopes
    public function scopeRewards($query)
    {
        return $query->where('type', RewardDisciplineType::REWARD);
    }

    public function scopeDisciplines($query)
    {
        return $query->where('type', RewardDisciplineType::DISCIPLINE);
    }

    public function scopeActive($query)
    {
        return $query->where('status', RewardDisciplineStatus::ACTIVE);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('effective_date', now()->year);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('effective_date')->orderByDesc('created_at');
    }

    // Helper methods
    public function getFormattedAmountAttribute(): string
    {
        if (!$this->amount) {
            return '-';
        }

        $prefix = $this->type === RewardDisciplineType::REWARD ? '+' : '-';
        return $prefix . number_format($this->amount, 0, ',', '.') . ' VND';
    }

    public function isReward(): bool
    {
        return $this->type === RewardDisciplineType::REWARD;
    }

    public function isDiscipline(): bool
    {
        return $this->type === RewardDisciplineType::DISCIPLINE;
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'type',
                'category',
                'decision_no',
                'decision_date',
                'effective_date',
                'amount',
                'description',
                'issued_by',
                'status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Tạo mới khen thưởng/kỷ luật',
                'updated' => 'Cập nhật khen thưởng/kỷ luật',
                'deleted' => 'Xóa khen thưởng/kỷ luật',
                default => $eventName
            })
            ->useLogName('reward-discipline');
    }

    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName)
    {
        $properties = $activity->properties->toArray();

        // Thêm thông tin employee
        $properties['employee_id'] = $this->employee_id;
        $properties['employee_name'] = $this->employee->full_name ?? null;

        // Thêm label hiển thị
        $typeLabel = $this->type->label();
        $categoryLabel = $this->category->label();
        $properties['label'] = "{$typeLabel}: {$categoryLabel} - QĐ {$this->decision_no}";

        // Thêm thông tin issued_by
        if ($this->issuedBy) {
            $properties['issued_by_name'] = $this->issuedBy->full_name;
        }

        $activity->properties = $properties;
    }
}
