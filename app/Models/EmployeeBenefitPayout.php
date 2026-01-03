<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeBenefitPayout extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'benefit_type_id',
        'paid_date',
        'amount',
        'currency',
        'note',
        'paid_by',
        'payment_method',
        'reference_no',
        'source',
    ];

    protected $casts = [
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship: Nhân viên
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relationship: Loại phúc lợi
     */
    public function benefitType(): BelongsTo
    {
        return $this->belongsTo(BenefitType::class, 'benefit_type_id');
    }

    /**
     * Relationship: Admin ghi nhận
     */
    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Relationship: Chứng từ đính kèm (polymorphic)
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'benefit_type_id', 'paid_date', 'amount', 'note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Filter by employee
     */
    public function scopeEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope: Filter by benefit type
     */
    public function scopeBenefitType($query, $typeId)
    {
        return $query->where('benefit_type_id', $typeId);
    }

    /**
     * Scope: Filter by year
     */
    public function scopeYear($query, $year)
    {
        return $query->whereYear('paid_date', $year);
    }

    /**
     * Scope: Filter by month
     */
    public function scopeMonth($query, $month)
    {
        return $query->whereMonth('paid_date', $month);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_date', [$startDate, $endDate]);
    }
}
