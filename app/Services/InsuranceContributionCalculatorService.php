<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\Employee;
use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use Carbon\Carbon;

/**
 * Service: Insurance Contribution Calculator
 *
 * Purpose: Calculate insurance contributions for employees based on:
 * - Base salary from active Contract/Appendix at declaration month
 * - Enabled components from InsuranceParticipation
 * - Component rates (default or custom)
 * - Base type (INSURANCE_SALARY or FIXED_AMOUNT for special cases like BHTN 72M)
 */
class InsuranceContributionCalculatorService
{
    /**
     * Calculate insurance contribution for an employee for a specific declaration month
     *
     * @param Employee $employee
     * @param string $declarationMonth Format: YYYY-MM
     * @return array [
     *     'employee_id' => UUID,
     *     'base_insurance_salary' => float,
     *     'total_amount' => float,
     *     'components' => [
     *         [
     *             'component_id' => int,
     *             'component_code' => string,
     *             'component_name' => string,
     *             'base_type' => string (INSURANCE_SALARY|FIXED_AMOUNT),
     *             'base_used' => float,
     *             'rate_total' => float,
     *             'amount' => float
     *         ],
     *         ...
     *     ]
     * ]
     * @throws \Exception if no active participation or contract found
     */
    public function calculateForEmployee(Employee $employee, string $declarationMonth): array
    {
        // Get active insurance participation
        $participation = $this->getActiveParticipation($employee);

        if (!$participation) {
            throw new \Exception("Employee {$employee->full_name} has no active insurance participation");
        }

        // Get base insurance salary from contract/appendix
        $baseInsuranceSalary = $this->getInsuranceSalary($employee, $declarationMonth);

        if ($baseInsuranceSalary === null) {
            throw new \Exception("Cannot determine insurance salary for employee {$employee->full_name} at {$declarationMonth}");
        }

        // Get enabled components
        $enabledComponents = $participation->components()
            ->where('is_enabled', true)
            ->with('component')
            ->get();

        if ($enabledComponents->isEmpty()) {
            throw new \Exception("Employee {$employee->full_name} has no enabled insurance components");
        }

        // Calculate contribution for each component
        $componentBreakdown = [];
        $totalAmount = 0;

        foreach ($enabledComponents as $participationComponent) {
            $component = $participationComponent->component;

            // Determine base amount to use
            if ($participationComponent->base_type === 'FIXED_AMOUNT') {
                // Use fixed base_amount (e.g., BHTN with 72M VND base)
                $baseUsed = $participationComponent->base_amount ?? 0;
            } else {
                // Use insurance salary from contract (default)
                $baseUsed = $baseInsuranceSalary;
            }

            // Calculate amount
            $rateTotal = $participationComponent->rate_total ?? $component->default_rate_total;
            $amount = $baseUsed * $rateTotal;
            $totalAmount += $amount;

            $componentBreakdown[] = [
                'component_id' => $component->id,
                'component_code' => $component->code,
                'component_name' => $component->name_vi,
                'base_type' => $participationComponent->base_type,
                'base_used' => round($baseUsed, 2),
                'rate_total' => $rateTotal,
                'amount' => round($amount, 2),
            ];
        }

        return [
            'employee_id' => $employee->id,
            'base_insurance_salary' => round($baseInsuranceSalary, 2),
            'total_amount' => round($totalAmount, 2),
            'components' => $componentBreakdown,
        ];
    }

    /**
     * Calculate contributions for multiple employees
     *
     * @param array|Employee[] $employees
     * @param string $declarationMonth
     * @return array Array of calculation results (same format as calculateForEmployee)
     */
    public function calculateForEmployees(array $employees, string $declarationMonth): array
    {
        $results = [];

        foreach ($employees as $employee) {
            try {
                $results[] = $this->calculateForEmployee($employee, $declarationMonth);
            } catch (\Exception $e) {
                // Log error but continue with other employees
                \Log::warning("Failed to calculate insurance for employee {$employee->id}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Get active insurance participation for an employee
     *
     * @param Employee $employee
     * @return InsuranceParticipation|null
     */
    protected function getActiveParticipation(Employee $employee): ?InsuranceParticipation
    {
        return $employee->insuranceParticipations()
            ->where('status', 'ACTIVE')
            ->latest('participation_start_date')
            ->first();
    }

    /**
     * Get insurance salary for employee at specific month
     * Looks for active Contract or ContractAppendix at that month
     *
     * @param Employee $employee
     * @param string $declarationMonth Format: YYYY-MM
     * @return float|null
     */
    protected function getInsuranceSalary(Employee $employee, string $declarationMonth): ?float
    {
        $declarationDate = Carbon::createFromFormat('Y-m', $declarationMonth)->startOfMonth();

        // First, try to find ContractAppendix active at declaration month
        $appendix = ContractAppendix::where('employee_id', $employee->id)
            ->where('appendix_start_date', '<=', $declarationDate)
            ->where(function ($query) use ($declarationDate) {
                $query->whereNull('appendix_end_date')
                    ->orWhere('appendix_end_date', '>=', $declarationDate);
            })
            ->orderBy('appendix_start_date', 'desc')
            ->first();

        if ($appendix && $appendix->insurance_salary !== null) {
            return $appendix->insurance_salary;
        }

        // If no appendix, try to find active Contract
        $contract = Contract::where('employee_id', $employee->id)
            ->where('start_date', '<=', $declarationDate)
            ->where(function ($query) use ($declarationDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $declarationDate);
            })
            ->where('status', 'ACTIVE')
            ->orderBy('start_date', 'desc')
            ->first();

        if ($contract && $contract->insurance_salary !== null) {
            return $contract->insurance_salary;
        }

        return null;
    }

    /**
     * Get summary statistics for a set of calculation results
     *
     * @param array $calculations Results from calculateForEmployees()
     * @return array [
     *     'total_employees' => int,
     *     'total_base_salary' => float,
     *     'total_contribution' => float,
     *     'by_component' => [
     *         'COMPONENT_CODE' => [
     *             'component_name' => string,
     *             'employee_count' => int,
     *             'total_base' => float,
     *             'total_amount' => float,
     *             'avg_rate' => float
     *         ],
     *         ...
     *     ]
     * ]
     */
    public function getSummaryStatistics(array $calculations): array
    {
        $totalEmployees = count($calculations);
        $totalBaseSalary = 0;
        $totalContribution = 0;
        $byComponent = [];

        foreach ($calculations as $calc) {
            $totalBaseSalary += $calc['base_insurance_salary'];
            $totalContribution += $calc['total_amount'];

            foreach ($calc['components'] as $component) {
                $code = $component['component_code'];

                if (!isset($byComponent[$code])) {
                    $byComponent[$code] = [
                        'component_name' => $component['component_name'],
                        'employee_count' => 0,
                        'total_base' => 0,
                        'total_amount' => 0,
                        'total_rate' => 0,
                    ];
                }

                $byComponent[$code]['employee_count']++;
                $byComponent[$code]['total_base'] += $component['base_used'];
                $byComponent[$code]['total_amount'] += $component['amount'];
                $byComponent[$code]['total_rate'] += $component['rate_total'];
            }
        }

        // Calculate average rates
        foreach ($byComponent as $code => &$data) {
            $data['avg_rate'] = $data['employee_count'] > 0
                ? $data['total_rate'] / $data['employee_count']
                : 0;
            unset($data['total_rate']);
        }

        return [
            'total_employees' => $totalEmployees,
            'total_base_salary' => round($totalBaseSalary, 2),
            'total_contribution' => round($totalContribution, 2),
            'by_component' => $byComponent,
        ];
    }

    /**
     * Validate if employee can have insurance calculated for declaration month
     *
     * @param Employee $employee
     * @param string $declarationMonth
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateEmployeeForCalculation(Employee $employee, string $declarationMonth): array
    {
        $errors = [];

        // Check active participation
        $participation = $this->getActiveParticipation($employee);
        if (!$participation) {
            $errors[] = 'No active insurance participation';
        } else {
            // Check if has any enabled components
            $enabledCount = $participation->components()->where('is_enabled', true)->count();
            if ($enabledCount === 0) {
                $errors[] = 'No enabled insurance components';
            }
        }

        // Check insurance salary
        $insuranceSalary = $this->getInsuranceSalary($employee, $declarationMonth);
        if ($insuranceSalary === null) {
            $errors[] = "Cannot determine insurance salary for {$declarationMonth}";
        } elseif ($insuranceSalary <= 0) {
            $errors[] = "Insurance salary must be greater than 0";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
