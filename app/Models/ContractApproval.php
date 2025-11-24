<?php

namespace App\Models;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContractApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'contract_id',
        'level',
        'order',
        'approver_id',
        'status',
        'comments',
        'approved_at',
    ];

    protected $casts = [
        'level' => ApprovalLevel::class,
        'status' => ApprovalStatus::class,
        'approved_at' => 'datetime',
        'order' => 'integer',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ApprovalStatus::REJECTED);
    }

    public function scopeForApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByLevel($query, ApprovalLevel $level)
    {
        return $query->where('level', $level);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatus::REJECTED;
    }
}
