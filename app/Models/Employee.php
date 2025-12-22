<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasUuids; // UUID cho khóa chính

    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'dob',
        'gender',
        'marital_status',
        'avatar',
        'cccd',
        'cccd_issued_on',
        'cccd_issued_by',
        'ward_id',
        'address_street',
        'temp_ward_id',
        'temp_address_street',
        'phone',
        'emergency_contact_phone',
        'personal_email',
        'company_email',
        'hire_date',
        'status',
        'si_number',
    ]; // Các trường cho phép gán hàng loạt

    protected $casts = [
        'dob' => 'date',
        'cccd_issued_on' => 'date',
        'hire_date' => 'date',
    ];

    // Quan hệ: tài khoản đăng nhập (nếu có)
    public function user() { return $this->belongsTo(\App\Models\User::class); }

    // Quan hệ: assignments (phân công/kiêm nhiệm) của nhân viên
    public function assignments() { return $this->hasMany(EmployeeAssignment::class); }

    // Lấy primary assignment đang ACTIVE
    public function primaryAssignment()
    {
        return $this->assignments()
            ->where('is_primary', true)
            ->where('status', 'ACTIVE')
            ->first();
    }

    // Quan hệ: ward thường trú (theo CCCD)
    public function ward() { return $this->belongsTo(\App\Models\Ward::class); }

    // Quan hệ: ward tạm trú
    public function tempWard() { return $this->belongsTo(\App\Models\Ward::class, 'temp_ward_id'); }

    // Quan hệ: trình độ học vấn
    public function educations(){ return $this->hasMany(EmployeeEducation::class); }

    // Quan hệ: người thân
    public function relatives(){ return $this->hasMany(EmployeeRelative::class); }

    // Quan hệ: kinh nghiệm làm việc
    public function experiences(){ return $this->hasMany(EmployeeExperience::class); }

    // Quan hệ: kỹ năng
    public function skills(){
        return $this->belongsToMany(Skill::class, 'employee_skills')
            ->using(EmployeeSkill::class)
            ->withPivot(['level','years','note'])
            ->withTimestamps();
    }

    // Quan hệ: employee_skills (records)
    public function employeeSkills(){ return $this->hasMany(EmployeeSkill::class); }

    // Quan hệ: employment periods (chu kỳ làm việc)
    public function employments()
    {
        return $this->hasMany(EmployeeEmployment::class, 'employee_id')->orderBy('start_date');
    }

    public function currentEmployment()
    {
        return $this->hasOne(EmployeeEmployment::class, 'employee_id')->whereNull('end_date');
    }

    // Quan hệ: khen thưởng & kỷ luật
    public function rewardsDisciplines(){ return $this->hasMany(EmployeeRewardDiscipline::class); }

    // ==================== TENURE / SENIORITY METHODS ====================

    /**
     * A) Current Tenure - Thâm niên đợt hiện tại
     * Tính từ employment hiện tại (is_current = true)
     * Dùng cho: đánh giá, thưởng, probation check
     */
    public function getCurrentTenure(): array
    {
        $current = $this->currentEmployment;

        if (!$current) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'total_days' => 0];
        }

        return $current->getFormattedDuration();
    }

    public function getCurrentTenureHuman(): string
    {
        $current = $this->currentEmployment;
        return $current ? $current->getHumanDuration() : '0 ngày';
    }

    /**
     * B) Cumulative Tenure - Thâm niên tích lũy tại công ty
     * Cộng tất cả các employment periods
     * Dùng cho: xét chế độ theo tổng thời gian làm việc
     */
    public function getCumulativeTenure(): array
    {
        // Use DateInterval sum for precise calculation
        $employments = $this->employments()->get();

        if ($employments->isEmpty()) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'total_days' => 0];
        }

        // Sum all employment durations using DateInterval for accuracy
        $totalYears = 0;
        $totalMonths = 0;
        $totalDays = 0;
        $totalDaysCount = 0;

        foreach ($employments as $employment) {
            $duration = $employment->getFormattedDuration();
            $totalYears += $duration['years'];
            $totalMonths += $duration['months'];
            $totalDays += $duration['days'];
            $totalDaysCount += $duration['total_days'];
        }

        // Normalize: convert excess days/months to upper units
        $totalMonths += floor($totalDays / 30);
        $totalDays = $totalDays % 30;

        $totalYears += floor($totalMonths / 12);
        $totalMonths = $totalMonths % 12;

        return [
            'years' => (int) $totalYears,
            'months' => (int) $totalMonths,
            'days' => (int) $totalDays,
            'total_days' => $totalDaysCount,
        ];
    }

    public function getCumulativeTenureHuman(): string
    {
        $tenure = $this->getCumulativeTenure();
        $parts = [];

        if ($tenure['years'] > 0) {
            $parts[] = $tenure['years'] . ' năm';
        }
        if ($tenure['months'] > 0) {
            $parts[] = $tenure['months'] . ' tháng';
        }
        if ($tenure['days'] > 0 || empty($parts)) {
            $parts[] = $tenure['days'] . ' ngày';
        }

        return implode(' ', $parts);
    }

    /**
     * C) Continuous Tenure - Thâm niên liên tục
     * Hiện tại = Current tenure (chưa có rule nghỉ ngắn ngày)
     */
    public function getContinuousTenure(): array
    {
        return $this->getCurrentTenure();
    }

    public function getContinuousTenureHuman(): string
    {
        return $this->getCurrentTenureHuman();
    }

    /**
     * Legacy methods (kept for backward compatibility)
     */
    public function getTotalSeniorityYears(): int
    {
        return $this->getCumulativeTenure()['years'];
    }

    public function getCurrentSeniorityYears(): int
    {
        return $this->getCurrentTenure()['years'];
    }

    /**
     * Get employment history for display
     * Returns array of periods with formatted info
     */
    public function getEmploymentHistory(): array
    {
        return $this->employments()
            ->orderBy('start_date')
            ->get()
            ->map(function ($employment) {
                return [
                    'id' => $employment->id,
                    'start_date' => $employment->start_date->format('d/m/Y'),
                    'end_date' => $employment->end_date ? $employment->end_date->format('d/m/Y') : 'nay',
                    'duration' => $employment->getHumanDuration(),
                    'is_current' => $employment->is_current,
                    'end_reason' => $employment->end_reason,
                ];
            })
            ->toArray();
    }

    // Quan hệ: hợp đồng
    public function contracts(){ return $this->hasMany(Contract::class); }

    // Quan hệ: nghỉ phép
    public function leaveRequests(){ return $this->hasMany(LeaveRequest::class); }
    public function leaveBalances(){ return $this->hasMany(LeaveBalance::class); }

    // Quan hệ: lương
    public function payrollItems(){ return $this->hasMany(PayrollItem::class); }

    // Quan hệ: bảo hiểm
    public function insuranceParticipations(){ return $this->hasMany(InsuranceParticipation::class); }
    public function insuranceChangeRecords(){ return $this->hasMany(InsuranceChangeRecord::class); }
    public function employeeAbsences(){ return $this->hasMany(EmployeeAbsence::class); }
}
