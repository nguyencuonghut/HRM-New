<?php

namespace App\Services;

use App\Models\InsuranceConfigSet;
use App\Models\InsuranceMinimumWageConfig;
use App\Models\InsuranceSalaryGradeConfig;
use Carbon\Carbon;

/**
 * Service: InsuranceConfigResolver
 *
 * Service trung tâm để resolve insurance config tại thời điểm cụ thể.
 * Thay thế việc query trực tiếp vào MinimumWage và PositionSalaryGrade.
 *
 * Mục đích:
 * - Single source of truth cho config resolution
 * - Cache-friendly (có thể thêm cache layer sau)
 * - Dễ test và maintain
 *
 * Usage:
 * ```php
 * $resolver = app(InsuranceConfigResolver::class);
 * $minWage = $resolver->getMinimumWage(2, '2026-01-08');
 * $coefficient = $resolver->getGradeCoefficient(3, '2026-01-08');
 * ```
 */
class InsuranceConfigResolver
{
    /**
     * Lấy active config set tại thời điểm cụ thể
     *
     * @param string|null $asOfDate Ngày cần lấy config (null = hôm nay)
     * @return InsuranceConfigSet|null
     */
    public function getActiveSet(?string $asOfDate = null): ?InsuranceConfigSet
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        return InsuranceConfigSet::active()
            ->effectiveAt($date)
            ->with(['minimumWages', 'salaryGrades'])
            ->first();
    }

    /**
     * Lấy lương tối thiểu vùng tại thời điểm cụ thể (chỉ trả về amount)
     *
     * @param int $region Vùng (1-4)
     * @param string|null $asOfDate Ngày cần lấy (null = hôm nay)
     * @return float|null Amount (VNĐ)
     */
    public function getMinimumWage(int $region, ?string $asOfDate = null): ?float
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Validate region
        if ($region < 1 || $region > 4) {
            return null;
        }

        // Lấy active config set
        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return null;
        }

        // Lấy minimum wage config cho vùng này
        $wageConfig = $configSet->minimumWages()
            ->where('region', $region)
            ->first();

        if (!$wageConfig) {
            return null;
        }

        return (float) $wageConfig->amount;
    }

    /**
     * Lấy thông tin đầy đủ lương tối thiểu vùng
     *
     * @param int $region Vùng (1-4)
     * @param string|null $asOfDate Ngày cần lấy (null = hôm nay)
     * @return array|null ['region', 'amount', 'formatted', 'note']
     */
    public function getMinimumWageDetail(int $region, ?string $asOfDate = null): ?array
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Validate region
        if ($region < 1 || $region > 4) {
            return null;
        }

        // Lấy active config set
        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return null;
        }

        // Lấy minimum wage config cho vùng này
        $wageConfig = $configSet->minimumWages()
            ->where('region', $region)
            ->first();

        if (!$wageConfig) {
            return null;
        }

        return [
            'region' => $wageConfig->region,
            'region_name' => $wageConfig->region_name,
            'amount' => (float) $wageConfig->amount,
            'formatted' => $wageConfig->formatted_amount,
            'note' => $wageConfig->note,
            'config_set_code' => $configSet->code,
            'effective_from' => $configSet->effective_from->format('Y-m-d'),
        ];
    }

    /**
     * Lấy hệ số bậc lương tại thời điểm cụ thể
     *
     * @param int $grade Bậc (1-7)
     * @param string|null $asOfDate Ngày cần lấy (null = hôm nay)
     * @return float|null Hệ số
     */
    public function getGradeCoefficient(int $grade, ?string $asOfDate = null): ?float
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Validate grade
        if ($grade < 1 || $grade > 7) {
            return null;
        }

        // Lấy active config set
        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return null;
        }

        // Lấy grade config
        $gradeConfig = $configSet->salaryGrades()
            ->where('grade', $grade)
            ->first();

        if (!$gradeConfig) {
            return null;
        }

        return (float) $gradeConfig->coefficient;
    }

    /**
     * Lấy chi tiết đầy đủ của grade config
     *
     * @param int $grade Bậc (1-7)
     * @param string|null $asOfDate Ngày cần lấy (null = hôm nay)
     * @return array|null ['grade', 'name', 'coefficient', 'description']
     */
    public function getGradeConfig(int $grade, ?string $asOfDate = null): ?array
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Validate grade
        if ($grade < 1 || $grade > 7) {
            return null;
        }

        // Lấy active config set
        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return null;
        }

        // Lấy grade config
        $gradeConfig = $configSet->salaryGrades()
            ->where('grade', $grade)
            ->first();

        if (!$gradeConfig) {
            return null;
        }

        return [
            'grade' => $gradeConfig->grade,
            'name' => $gradeConfig->name,
            'coefficient' => (float) $gradeConfig->coefficient,
            'formatted_coefficient' => $gradeConfig->formatted_coefficient,
            'description' => $gradeConfig->description,
            'config_set_code' => $configSet->code,
        ];
    }

    /**
     * Tính lương BHXH cho một bậc và vùng cụ thể (chỉ trả về amount)
     *
     * @param int $region Vùng (1-4)
     * @param int $grade Bậc (1-7)
     * @param string|null $asOfDate Ngày tính (null = hôm nay)
     * @return float|null Insurance salary amount
     */
    public function calculate(int $region, int $grade, ?string $asOfDate = null): ?float
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Lấy minimum wage
        $minWage = $this->getMinimumWage($region, $date);
        if (!$minWage) {
            return null;
        }

        // Lấy coefficient
        $coefficient = $this->getGradeCoefficient($grade, $date);
        if (!$coefficient) {
            return null;
        }

        // Tính lương BHXH
        return $minWage * $coefficient;
    }

    /**
     * Tính lương BHXH với breakdown đầy đủ
     *
     * @param int $region Vùng (1-4)
     * @param int $grade Bậc (1-7)
     * @param string|null $asOfDate Ngày tính (null = hôm nay)
     * @return array|null ['amount', 'minimum_wage', 'coefficient', 'breakdown']
     */
    public function calculateDetailed(int $region, int $grade, ?string $asOfDate = null): ?array
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        // Lấy minimum wage detail
        $minWageDetail = $this->getMinimumWageDetail($region, $date);
        if (!$minWageDetail) {
            return null;
        }

        // Lấy coefficient
        $coefficient = $this->getGradeCoefficient($grade, $date);
        if (!$coefficient) {
            return null;
        }

        // Tính lương BHXH
        $amount = $minWageDetail['amount'] * $coefficient;

        return [
            'amount' => $amount,
            'minimum_wage' => $minWageDetail['amount'],
            'coefficient' => $coefficient,
            'breakdown' => [
                'region' => $region,
                'region_name' => $minWageDetail['region_name'],
                'grade' => $grade,
                'minimum_wage_amount' => $minWageDetail['amount'],
                'minimum_wage_formatted' => $minWageDetail['formatted'],
                'coefficient' => $coefficient,
                'amount' => $amount,
                'amount_formatted' => number_format($amount, 0, ',', '.') . ' VNĐ',
                'formula' => "{$minWageDetail['amount']} × {$coefficient} = {$amount}",
                'calculation_date' => $date,
                'config_set_code' => $minWageDetail['config_set_code'],
            ],
        ];
    }

    /**
     * Lấy tất cả minimum wages (4 vùng) tại thời điểm cụ thể
     *
     * @param string|null $asOfDate
     * @return array Array of 4 regions
     */
    public function getAllMinimumWages(?string $asOfDate = null): array
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return [];
        }

        return $configSet->minimumWages->map(function ($wage) use ($configSet) {
            return [
                'region' => $wage->region,
                'region_name' => $wage->region_name,
                'amount' => (float) $wage->amount,
                'formatted' => $wage->formatted_amount,
                'note' => $wage->note,
            ];
        })->toArray();
    }

    /**
     * Lấy tất cả salary grades (7 bậc) tại thời điểm cụ thể
     *
     * @param string|null $asOfDate
     * @return array Array of 7 grades
     */
    public function getAllGrades(?string $asOfDate = null): array
    {
        $date = $asOfDate ?? now()->format('Y-m-d');

        $configSet = $this->getActiveSet($date);

        if (!$configSet) {
            return [];
        }

        return $configSet->salaryGrades->map(function ($grade) {
            return [
                'grade' => $grade->grade,
                'name' => $grade->name,
                'coefficient' => (float) $grade->coefficient,
                'formatted_coefficient' => $grade->formatted_coefficient,
                'description' => $grade->description,
            ];
        })->toArray();
    }

    /**
     * Kiểm tra xem có active config set không
     *
     * @param string|null $asOfDate
     * @return bool
     */
    public function hasActiveConfig(?string $asOfDate = null): bool
    {
        return $this->getActiveSet($asOfDate) !== null;
    }

    /**
     * Validate input parameters
     *
     * @param int|null $region
     * @param int|null $grade
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateInput(?int $region = null, ?int $grade = null): array
    {
        $errors = [];

        if ($region !== null && ($region < 1 || $region > 4)) {
            $errors[] = 'Vùng phải từ 1 đến 4';
        }

        if ($grade !== null && ($grade < 1 || $grade > 7)) {
            $errors[] = 'Bậc phải từ 1 đến 7';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Lấy thông tin config set hiện tại
     *
     * @param string|null $asOfDate
     * @return array|null ['code', 'name', 'effective_from', 'effective_to', 'status']
     */
    public function getCurrentConfigInfo(?string $asOfDate = null): ?array
    {
        $configSet = $this->getActiveSet($asOfDate);

        if (!$configSet) {
            return null;
        }

        return [
            'id' => $configSet->id,
            'code' => $configSet->code,
            'name' => $configSet->name,
            'description' => $configSet->description,
            'status' => $configSet->status,
            'status_label' => $configSet->status_label,
            'effective_from' => $configSet->effective_from->format('Y-m-d'),
            'effective_to' => $configSet->effective_to?->format('Y-m-d'),
            'effective_period' => $configSet->effective_period,
        ];
    }
}
