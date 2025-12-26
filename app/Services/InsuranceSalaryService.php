<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeInsuranceProfile;
use App\Models\MinimumWage;
use App\Models\PositionSalaryGrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service: Quản lý lương BHXH theo hệ thống Thang - Bậc - Hệ Số
 *
 * Chức năng:
 * - Tính lương BHXH tại thời điểm cụ thể
 * - Tính thâm niên theo vị trí
 * - Đề xuất tăng bậc
 * - Khởi tạo hồ sơ BHXH
 */
class InsuranceSalaryService
{
    /**
     * Tính lương BHXH cho nhân viên tại thời điểm cụ thể
     *
     * @param Employee $employee
     * @param int $region Vùng lương tối thiểu (1-4)
     * @param string|null $date Ngày tính (null = hôm nay)
     * @return array|null ['amount', 'breakdown']
     */
    public function calculateInsuranceSalary(Employee $employee, int $region, ?string $date = null): ?array
    {
        $date = $date ?? now()->format('Y-m-d');

        // Lấy hồ sơ BHXH tại thời điểm
        $profile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
            ->atDate($date)
            ->first();

        if (!$profile || !$profile->position_id) {
            return null;
        }

        // Lấy hệ số bậc
        $gradeData = PositionSalaryGrade::where('position_id', $profile->position_id)
            ->where('grade', $profile->grade)
            ->effectiveAt($date)
            ->where('is_active', true)
            ->first();

        if (!$gradeData) {
            return null;
        }

        // Lấy lương tối thiểu vùng
        $minWage = MinimumWage::getForRegion($region, $date);

        if (!$minWage) {
            return null;
        }

        // Tính lương BHXH
        $amount = $minWage->amount * $gradeData->coefficient;

        return [
            'amount' => $amount,
            'breakdown' => [
                'region' => $region,
                'region_name' => $minWage->region_name,
                'minimum_wage' => $minWage->amount,
                'minimum_wage_effective_from' => $minWage->effective_from->format('d/m/Y'),
                'position' => $profile->position->title ?? null,
                'grade' => $profile->grade,
                'coefficient' => $gradeData->coefficient,
                'formula' => "{$minWage->amount} × {$gradeData->coefficient} = {$amount}",
            ],
        ];
    }

    /**
     * Tính thâm niên ở cùng vị trí (tính theo năm)
     *
     * @param Employee $employee
     * @param string $positionId
     * @param string|null $untilDate Tính đến ngày (null = hôm nay)
     * @return float Số năm (có thể là số thập phân)
     */
    public function calculateTenureInPosition(Employee $employee, string $positionId, ?string $untilDate = null): float
    {
        $untilDate = $untilDate ?? now()->format('Y-m-d');

        // Lấy tất cả các khoảng thời gian ở vị trí này
        $profiles = EmployeeInsuranceProfile::where('employee_id', $employee->id)
            ->where('position_id', $positionId)
            ->where('applied_from', '<=', $untilDate)
            ->orderBy('applied_from')
            ->get();

        if ($profiles->isEmpty()) {
            return 0;
        }

        $totalDays = 0;

        foreach ($profiles as $profile) {
            $start = Carbon::parse($profile->applied_from);

            // Nếu applied_to null → đang áp dụng
            $end = $profile->applied_to
                ? Carbon::parse(min($profile->applied_to, $untilDate))
                : Carbon::parse($untilDate);

            // Chỉ tính nếu khoảng thời gian hợp lệ
            if ($end->gte($start)) {
                $totalDays += $start->diffInDays($end);
            }
        }

        // Convert sang năm (365 ngày = 1 năm)
        return round($totalDays / 365, 2);
    }

    /**
     * Đề xuất bậc mới dựa trên thâm niên (cứ 3 năm tăng 1 bậc)
     *
     * @param Employee $employee
     * @return array|null ['current_grade', 'suggested_grade', 'tenure_years', 'eligible']
     */
    public function suggestGradeRaise(Employee $employee): ?array
    {
        $profile = $employee->currentInsuranceProfile;

        if (!$profile || !$profile->position_id) {
            return null;
        }

        // Tính thâm niên tại vị trí hiện tại
        $tenureYears = $this->calculateTenureInPosition($employee, $profile->position_id);

        // Bậc mục tiêu = min(7, 1 + floor(tenure_years / 3))
        $suggestedGrade = min(7, 1 + floor($tenureYears / 3));

        // Đủ điều kiện tăng bậc?
        $eligible = $suggestedGrade > $profile->grade;

        return [
            'current_grade' => $profile->grade,
            'suggested_grade' => $suggestedGrade,
            'tenure_years' => $tenureYears,
            'eligible' => $eligible,
            'next_raise_in_years' => $eligible ? 0 : (3 - ($tenureYears % 3)),
        ];
    }

    /**
     * Khởi tạo hồ sơ BHXH cho nhân viên mới
     *
     * @param Employee $employee
     * @param string $positionId
     * @param int $grade Bậc ban đầu (default = 1)
     * @param string|null $appliedFrom Ngày áp dụng (default = hire_date)
     * @return EmployeeInsuranceProfile
     */
    public function initializeInsuranceProfile(
        Employee $employee,
        string $positionId,
        int $grade = 1,
        ?string $appliedFrom = null
    ): EmployeeInsuranceProfile {
        $appliedFrom = $appliedFrom ?? $employee->hire_date ?? now()->format('Y-m-d');

        return EmployeeInsuranceProfile::create([
            'employee_id' => $employee->id,
            'position_id' => $positionId,
            'grade' => $grade,
            'applied_from' => $appliedFrom,
            'applied_to' => null,
            'reason' => 'INITIAL',
            'note' => 'Khởi tạo hồ sơ BHXH lúc nhập việc',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Tăng bậc cho nhân viên (tạo record mới, đóng record cũ)
     *
     * @param Employee $employee
     * @param int $newGrade
     * @param string $effectiveDate Ngày hiệu lực
     * @param string $reason Lý do (SENIORITY, PROMOTION, ADJUSTMENT...)
     * @param string|null $appendixId ID phụ lục làm căn cứ pháp lý
     * @param string|null $note Ghi chú
     * @return EmployeeInsuranceProfile
     */
    public function raiseGrade(
        Employee $employee,
        int $newGrade,
        string $effectiveDate,
        string $reason,
        ?string $appendixId = null,
        ?string $note = null
    ): EmployeeInsuranceProfile {
        return DB::transaction(function () use (
            $employee,
            $newGrade,
            $effectiveDate,
            $reason,
            $appendixId,
            $note
        ) {
            // Đóng profile cũ
            $oldProfile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
                ->current()
                ->first();

            if ($oldProfile) {
                $oldProfile->applied_to = Carbon::parse($effectiveDate)->subDay()->format('Y-m-d');
                $oldProfile->save();
            }

            // Tạo profile mới
            return EmployeeInsuranceProfile::create([
                'employee_id' => $employee->id,
                'position_id' => $oldProfile?->position_id,
                'grade' => $newGrade,
                'applied_from' => $effectiveDate,
                'applied_to' => null,
                'reason' => $reason,
                'source_appendix_id' => $appendixId,
                'note' => $note ?? "Tăng bậc từ {$oldProfile?->grade} lên {$newGrade}",
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Lấy danh sách nhân viên đủ điều kiện tăng bậc (cron job)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeesEligibleForGradeRaise()
    {
        $employees = Employee::with('currentInsuranceProfile.position')
            ->active()
            ->get();

        $eligible = [];

        foreach ($employees as $employee) {
            $suggestion = $this->suggestGradeRaise($employee);

            if ($suggestion && $suggestion['eligible']) {
                $eligible[] = [
                    'employee' => $employee,
                    'suggestion' => $suggestion,
                ];
            }
        }

        return collect($eligible);
    }

    /**
     * Lấy lịch sử thay đổi bậc BHXH
     *
     * @param Employee $employee
     * @return \Illuminate\Support\Collection
     */
    public function getInsuranceHistory(Employee $employee)
    {
        return EmployeeInsuranceProfile::where('employee_id', $employee->id)
            ->with(['position', 'sourceAppendix'])
            ->orderBy('applied_from', 'desc')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'period' => $profile->applied_from->format('d/m/Y') . ' - ' .
                               ($profile->applied_to ? $profile->applied_to->format('d/m/Y') : 'Hiện tại'),
                    'position' => $profile->position?->title,
                    'grade' => $profile->grade,
                    'reason' => $profile->reason,
                    'reason_display' => $this->getReasonDisplay($profile->reason),
                    'appendix' => $profile->sourceAppendix?->id,
                    'note' => $profile->note,
                ];
            });
    }

    /**
     * Hiển thị tên lý do thay đổi
     */
    private function getReasonDisplay(?string $reason): string
    {
        $reasons = [
            'INITIAL' => 'Khởi tạo ban đầu',
            'SENIORITY' => 'Tăng bậc theo thâm niên',
            'PROMOTION' => 'Thăng chức',
            'ADJUSTMENT' => 'Điều chỉnh đặc biệt',
            'POSITION_CHANGE' => 'Chuyển vị trí',
            'BACKFILL' => 'Bổ sung dữ liệu lịch sử',
        ];

        return $reasons[$reason] ?? $reason ?? 'Không xác định';
    }
}
