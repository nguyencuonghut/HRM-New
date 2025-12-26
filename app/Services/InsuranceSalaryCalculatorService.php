<?php

namespace App\Services;

use App\Models\MinimumWage;
use App\Models\PositionSalaryGrade;
use App\Models\EmployeeInsuranceProfile;
use Carbon\Carbon;

/**
 * Service: Tính toán lương BHXH (Calculator)
 *
 * Service này TẬP TRUNG vào TÍNH TOÁN thuần túy (pure calculation)
 * Không xử lý business logic phức tạp (tạo records, cập nhật...)
 *
 * Sử dụng:
 * - Payroll module (tính lương BHXH cho kỳ lương)
 * - BHXH Report (báo cáo đóng BHXH)
 * - Contract/Appendix (preview lương BHXH trước khi tạo)
 * - UI display (card BHXH trong profile)
 */
class InsuranceSalaryCalculatorService
{
    /**
     * Tính lương BHXH = Lương tối thiểu vùng × Hệ số bậc
     *
     * @param int $region Vùng (1-4)
     * @param string $positionId ID vị trí
     * @param int $grade Bậc (1-7)
     * @param string|null $effectiveDate Ngày tính (null = hôm nay)
     * @return array|null ['amount', 'minimum_wage', 'coefficient', 'breakdown']
     */
    public function calculate(
        int $region,
        string $positionId,
        int $grade,
        ?string $effectiveDate = null
    ): ?array {
        $effectiveDate = $effectiveDate ?? now()->format('Y-m-d');

        // Lấy lương tối thiểu vùng
        $minWage = $this->getMinimumWage($region, $effectiveDate);

        if (!$minWage) {
            return null;
        }

        // Lấy hệ số bậc
        $gradeData = $this->getGradeCoefficient($positionId, $grade, $effectiveDate);

        if (!$gradeData) {
            return null;
        }

        // Tính lương BHXH
        $amount = $minWage['amount'] * $gradeData['coefficient'];

        return [
            'amount' => $amount,
            'minimum_wage' => $minWage['amount'],
            'coefficient' => $gradeData['coefficient'],
            'breakdown' => [
                'region' => $region,
                'region_name' => $minWage['region_name'],
                'minimum_wage_amount' => $minWage['amount'],
                'minimum_wage_formatted' => number_format($minWage['amount'], 0, ',', '.') . ' VNĐ',
                'minimum_wage_effective_from' => $minWage['effective_from'],
                'position_id' => $positionId,
                'grade' => $grade,
                'coefficient' => $gradeData['coefficient'],
                'amount' => $amount,
                'amount_formatted' => number_format($amount, 0, ',', '.') . ' VNĐ',
                'formula' => "{$minWage['amount']} × {$gradeData['coefficient']} = {$amount}",
                'calculation_date' => $effectiveDate,
            ],
        ];
    }

    /**
     * Tính lương BHXH cho nhân viên dựa trên insurance profile
     *
     * @param string $employeeId
     * @param int $region Vùng
     * @param string|null $date Ngày tính (null = hôm nay)
     * @return array|null
     */
    public function calculateForEmployee(
        string $employeeId,
        int $region,
        ?string $date = null
    ): ?array {
        $date = $date ?? now()->format('Y-m-d');

        // Lấy insurance profile tại thời điểm
        $profile = EmployeeInsuranceProfile::where('employee_id', $employeeId)
            ->where('applied_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('applied_to')
                  ->orWhere('applied_to', '>=', $date);
            })
            ->with('position')
            ->first();

        if (!$profile || !$profile->position_id) {
            return null;
        }

        $result = $this->calculate(
            $region,
            $profile->position_id,
            $profile->grade,
            $date
        );

        if ($result) {
            $result['breakdown']['employee_id'] = $employeeId;
            $result['breakdown']['position_title'] = $profile->position->title ?? null;
            $result['breakdown']['applied_from'] = $profile->applied_from->format('d/m/Y');
            $result['breakdown']['applied_to'] = $profile->applied_to?->format('d/m/Y');
        }

        return $result;
    }

    /**
     * Tính lương BHXH cho tất cả 7 bậc của một vị trí
     * (Dùng để hiển thị thang lương)
     *
     * @param string $positionId
     * @param int $region
     * @param string|null $date
     * @return array Array of 7 grades with calculated salaries
     */
    public function calculateAllGrades(
        string $positionId,
        int $region,
        ?string $date = null
    ): array {
        $date = $date ?? now()->format('Y-m-d');

        $results = [];

        for ($grade = 1; $grade <= 7; $grade++) {
            $calculation = $this->calculate($region, $positionId, $grade, $date);

            if ($calculation) {
                $results[] = [
                    'grade' => $grade,
                    'coefficient' => $calculation['coefficient'],
                    'amount' => $calculation['amount'],
                    'formatted' => $calculation['breakdown']['amount_formatted'],
                ];
            } else {
                // Nếu không có data cho bậc này, để null
                $results[] = [
                    'grade' => $grade,
                    'coefficient' => null,
                    'amount' => null,
                    'formatted' => 'Chưa có dữ liệu',
                ];
            }
        }

        return $results;
    }

    /**
     * So sánh lương BHXH giữa các bậc
     * (Dùng để hiển thị "nếu tăng bậc thì lương sẽ là...")
     *
     * @param string $positionId
     * @param int $currentGrade
     * @param int $newGrade
     * @param int $region
     * @param string|null $date
     * @return array|null ['current', 'new', 'difference', 'increase_percent']
     */
    public function compareGrades(
        string $positionId,
        int $currentGrade,
        int $newGrade,
        int $region,
        ?string $date = null
    ): ?array {
        $current = $this->calculate($region, $positionId, $currentGrade, $date);
        $new = $this->calculate($region, $positionId, $newGrade, $date);

        if (!$current || !$new) {
            return null;
        }

        $difference = $new['amount'] - $current['amount'];
        $increasePercent = ($difference / $current['amount']) * 100;

        return [
            'current' => [
                'grade' => $currentGrade,
                'amount' => $current['amount'],
                'formatted' => $current['breakdown']['amount_formatted'],
                'coefficient' => $current['coefficient'],
            ],
            'new' => [
                'grade' => $newGrade,
                'amount' => $new['amount'],
                'formatted' => $new['breakdown']['amount_formatted'],
                'coefficient' => $new['coefficient'],
            ],
            'difference' => [
                'amount' => $difference,
                'formatted' => number_format($difference, 0, ',', '.') . ' VNĐ',
                'percent' => round($increasePercent, 2),
                'formatted_percent' => round($increasePercent, 2) . '%',
            ],
        ];
    }

    /**
     * Lấy lương tối thiểu vùng tại thời điểm
     *
     * @param int $region
     * @param string $date
     * @return array|null ['amount', 'region_name', 'effective_from']
     */
    public function getMinimumWage(int $region, string $date): ?array
    {
        $minWage = MinimumWage::where('region', $region)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            })
            ->where('is_active', true)
            ->orderBy('effective_from', 'desc')
            ->first();

        if (!$minWage) {
            return null;
        }

        return [
            'id' => $minWage->id,
            'region' => $minWage->region,
            'region_name' => $minWage->region_name,
            'amount' => $minWage->amount,
            'effective_from' => $minWage->effective_from->format('d/m/Y'),
            'note' => $minWage->note,
        ];
    }

    /**
     * Lấy hệ số bậc tại thời điểm
     *
     * @param string $positionId
     * @param int $grade
     * @param string $date
     * @return array|null ['coefficient', 'effective_from']
     */
    public function getGradeCoefficient(string $positionId, int $grade, string $date): ?array
    {
        $gradeData = PositionSalaryGrade::where('position_id', $positionId)
            ->where('grade', $grade)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            })
            ->where('is_active', true)
            ->orderBy('effective_from', 'desc')
            ->first();

        if (!$gradeData) {
            return null;
        }

        return [
            'id' => $gradeData->id,
            'position_id' => $gradeData->position_id,
            'grade' => $gradeData->grade,
            'coefficient' => (float) $gradeData->coefficient,
            'effective_from' => $gradeData->effective_from->format('d/m/Y'),
            'note' => $gradeData->note,
        ];
    }

    /**
     * Kiểm tra xem position có thang lương chưa
     *
     * @param string $positionId
     * @return bool
     */
    public function hasGradeScale(string $positionId): bool
    {
        return PositionSalaryGrade::where('position_id', $positionId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Lấy tất cả vùng lương tối thiểu hiện tại (4 vùng)
     *
     * @return array Array of 4 regions
     */
    public function getAllCurrentMinimumWages(): array
    {
        $wages = MinimumWage::where('is_active', true)
            ->whereNull('effective_to')
            ->orderBy('region')
            ->get();

        return $wages->map(function ($wage) {
            return [
                'region' => $wage->region,
                'region_name' => $wage->region_name,
                'amount' => $wage->amount,
                'formatted' => $wage->formatted_amount,
                'effective_from' => $wage->effective_from->format('d/m/Y'),
            ];
        })->toArray();
    }

    /**
     * Tính tổng đóng BHXH cho một danh sách nhân viên (payroll)
     *
     * @param array $employees Array of ['employee_id' => id, 'region' => region]
     * @param string|null $date
     * @return array ['total', 'employees' => [...]]
     */
    public function calculateBulk(array $employees, ?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');
        $results = [];
        $total = 0;

        foreach ($employees as $emp) {
            $calculation = $this->calculateForEmployee(
                $emp['employee_id'],
                $emp['region'],
                $date
            );

            if ($calculation) {
                $results[] = [
                    'employee_id' => $emp['employee_id'],
                    'calculation' => $calculation,
                ];
                $total += $calculation['amount'];
            }
        }

        return [
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.') . ' VNĐ',
            'count' => count($results),
            'employees' => $results,
            'calculation_date' => $date,
        ];
    }

    /**
     * Format lương BHXH (helper)
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Validate dữ liệu đầu vào
     *
     * @param int $region
     * @param int $grade
     * @return array ['valid' => bool, 'errors' => [...]]
     */
    public function validate(int $region, int $grade): array
    {
        $errors = [];

        if ($region < 1 || $region > 4) {
            $errors[] = 'Vùng phải từ 1 đến 4';
        }

        if ($grade < 1 || $grade > 7) {
            $errors[] = 'Bậc phải từ 1 đến 7';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
