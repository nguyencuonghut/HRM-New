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

class EmployeeRewardDiscipline extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
}
