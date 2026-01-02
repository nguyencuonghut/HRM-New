<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeAnnualReview extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'employee_annual_reviews';

    protected $fillable = [
        'employee_id',
        'year',
        'kpi_avg_score',
        'final_rating',
        'final_score',
        'note',
        'input_by',
        'input_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'kpi_avg_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'input_at' => 'datetime',
    ];

    /**
     * Relationship: Nhân viên
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relationship: Người nhập
     */
    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    /**
     * Relationship: File đánh giá (polymorphic)
     * Nếu hệ thống có bảng attachments polymorphic
     */
    // public function attachments(): MorphMany
    // {
    //     return $this->morphMany(Attachment::class, 'attachable');
    // }

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'year', 'kpi_avg_score', 'final_rating', 'final_score', 'note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Filter by year
     */
    public function scopeYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Filter by employee
     */
    public function scopeEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Get rating label
     */
    public function getRatingLabelAttribute(): string
    {
        $labels = [
            'A' => 'Xuất sắc',
            'B' => 'Tốt',
            'C' => 'Đạt',
            'D' => 'Cần cải thiện',
        ];

        return $labels[$this->final_rating] ?? $this->final_rating;
    }
}
