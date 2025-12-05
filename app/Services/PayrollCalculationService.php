<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollCalculationService
{
    // Tỷ lệ khấu trừ BHXH theo quy định
    public const SOCIAL_INSURANCE_RATE = 0.08;      // 8%
    public const HEALTH_INSURANCE_RATE = 0.015;     // 1.5%
    public const UNEMPLOYMENT_INSURANCE_RATE = 0.01; // 1%

    // Phụ cấp chức vụ theo role_type
    public const POSITION_ALLOWANCES = [
        'HEAD' => 2000000,      // 2 triệu cho Trưởng phòng
        'DEPUTY' => 1000000,    // 1 triệu cho Phó phòng
        'MEMBER' => 0,          // Không có phụ cấp
    ];

    // Giảm trừ gia cảnh
    public const PERSONAL_DEDUCTION = 11000000;     // 11 triệu
    public const DEPENDENT_DEDUCTION = 4400000;     // 4.4 triệu/người

    /**
     * Calculate payroll for a specific employee and period
     *
     * @param Employee $employee
     * @param PayrollPeriod $period
     * @param array $options Additional calculation options
     * @return PayrollItem
     */
    public function calculatePayroll(Employee $employee, PayrollPeriod $period, array $options = []): PayrollItem
    {
        // Get active contract
        $contract = $this->getActiveContract($employee, $period);
        if (!$contract) {
            throw new \Exception("No active contract found for employee {$employee->full_name}");
        }

        // Get PRIMARY assignment
        $assignment = $this->getPrimaryAssignment($employee);

        // Initialize data
        $data = [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'contract_id' => $contract->id,
            'assignment_id' => $assignment?->id,
        ];

        // 1. Base salary from Contract
        $data['base_salary'] = $contract->base_salary ?? 0;

        // 2. Snapshot assignment info
        if ($assignment) {
            $data['department_name'] = $assignment->department?->name;
            $data['position_title'] = $assignment->position?->title;
            $data['role_type'] = $assignment->role_type;

            // Calculate position allowance based on role_type
            $data['position_allowance'] = self::POSITION_ALLOWANCES[$assignment->role_type] ?? 0;
        } else {
            $data['position_allowance'] = 0;
        }

        // 3. Other allowances (can be passed in options or calculated)
        $data['responsibility_allowance'] = $options['responsibility_allowance'] ?? 0;
        $data['other_allowances'] = $options['other_allowances'] ?? 0;
        $data['total_allowances'] = $data['position_allowance']
            + $data['responsibility_allowance']
            + $data['other_allowances'];

        // 4. Calculate working days and attendance rate
        $workingDays = $options['working_days'] ?? $this->calculateWorkingDays($period);
        $standardDays = $options['standard_days'] ?? 22;
        $data['working_days'] = $workingDays;
        $data['standard_days'] = $standardDays;
        $data['attendance_rate'] = ($workingDays / $standardDays) * 100;

        // 5. Calculate gross salary (base + allowances) adjusted by attendance
        $fullGrossSalary = $data['base_salary'] + $data['total_allowances'];
        $data['gross_salary'] = $fullGrossSalary * ($data['attendance_rate'] / 100);

        // 6. Calculate deductions
        $deductions = $this->calculateDeductions($data['gross_salary'], $employee, $options);
        $data['social_insurance'] = $deductions['social_insurance'];
        $data['health_insurance'] = $deductions['health_insurance'];
        $data['unemployment_insurance'] = $deductions['unemployment_insurance'];
        $data['income_tax'] = $deductions['income_tax'];
        $data['other_deductions'] = $options['other_deductions'] ?? 0;
        $data['total_deductions'] = $deductions['total'];

        // 7. Calculate net salary
        $data['net_salary'] = $data['gross_salary'] - $data['total_deductions'];

        // 8. Store calculation details as JSON
        $data['calculation_details'] = [
            'contract_number' => $contract->contract_number,
            'calculation_date' => now()->toDateTimeString(),
            'base_salary_breakdown' => [
                'monthly_base' => $data['base_salary'],
                'position_allowance' => $data['position_allowance'],
                'role_type' => $data['role_type'] ?? null,
            ],
            'attendance' => [
                'working_days' => $workingDays,
                'standard_days' => $standardDays,
                'rate' => round($data['attendance_rate'], 2),
            ],
            'deductions_breakdown' => $deductions['breakdown'] ?? [],
            'tax_calculation' => $deductions['tax_details'] ?? [],
        ];

        // Create or update payroll item
        $payrollItem = PayrollItem::updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
            ],
            $data
        );

        // Log activity
        activity()
            ->useLog('payroll-item')
            ->performedOn($payrollItem)
            ->withProperties([
                'employee_name' => $employee->full_name,
                'period' => $period->getPeriodName(),
                'net_salary' => $data['net_salary'],
            ])
            ->log('Tính lương cho nhân viên');

        return $payrollItem;
    }

    /**
     * Get active contract for employee at period
     */
    protected function getActiveContract(Employee $employee, PayrollPeriod $period): ?Contract
    {
        $periodDate = Carbon::create($period->year, $period->month, 1);

        return Contract::where('employee_id', $employee->id)
            ->where('status', 'ACTIVE')
            ->where('start_date', '<=', $periodDate)
            ->where(function ($query) use ($periodDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $periodDate);
            })
            ->first();
    }

    /**
     * Get PRIMARY assignment for employee
     */
    protected function getPrimaryAssignment(Employee $employee): ?EmployeeAssignment
    {
        return EmployeeAssignment::where('employee_id', $employee->id)
            ->where('is_primary', true)
            ->where('status', 'ACTIVE')
            ->with(['department', 'position'])
            ->first();
    }

    /**
     * Calculate working days in the period
     * For now, returns standard 22 days (can be enhanced with attendance tracking)
     */
    protected function calculateWorkingDays(PayrollPeriod $period): int
    {
        // TODO: Integrate with actual attendance system
        // For now, calculate working days excluding weekends
        $start = Carbon::create($period->year, $period->month, 1);
        $end = $start->copy()->endOfMonth();

        $workingDays = 0;
        $current = $start->copy();

        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate all deductions (insurance + tax)
     */
    protected function calculateDeductions(float $grossSalary, Employee $employee, array $options = []): array
    {
        // 1. Insurance deductions (based on gross salary)
        $socialInsurance = $grossSalary * self::SOCIAL_INSURANCE_RATE;
        $healthInsurance = $grossSalary * self::HEALTH_INSURANCE_RATE;
        $unemploymentInsurance = $grossSalary * self::UNEMPLOYMENT_INSURANCE_RATE;

        $totalInsurance = $socialInsurance + $healthInsurance + $unemploymentInsurance;

        // 2. Taxable income = gross - insurance - personal deduction - dependent deduction
        $taxableIncome = $grossSalary - $totalInsurance;

        // Personal deduction
        $taxableIncome -= self::PERSONAL_DEDUCTION;

        // Dependent deduction (can be passed in options or from employee data)
        $dependents = $options['dependents'] ?? $this->getDependentsCount($employee);
        $dependentDeduction = $dependents * self::DEPENDENT_DEDUCTION;
        $taxableIncome -= $dependentDeduction;

        // 3. Calculate progressive income tax
        $incomeTax = $this->calculateProgressiveTax(max(0, $taxableIncome));

        $breakdown = [
            'social_insurance' => [
                'rate' => self::SOCIAL_INSURANCE_RATE * 100 . '%',
                'amount' => $socialInsurance,
            ],
            'health_insurance' => [
                'rate' => self::HEALTH_INSURANCE_RATE * 100 . '%',
                'amount' => $healthInsurance,
            ],
            'unemployment_insurance' => [
                'rate' => self::UNEMPLOYMENT_INSURANCE_RATE * 100 . '%',
                'amount' => $unemploymentInsurance,
            ],
        ];

        $taxDetails = [
            'gross_salary' => $grossSalary,
            'insurance_deduction' => $totalInsurance,
            'personal_deduction' => self::PERSONAL_DEDUCTION,
            'dependent_deduction' => $dependentDeduction,
            'dependents_count' => $dependents,
            'taxable_income' => max(0, $taxableIncome),
            'income_tax' => $incomeTax,
        ];

        return [
            'social_insurance' => round($socialInsurance, 2),
            'health_insurance' => round($healthInsurance, 2),
            'unemployment_insurance' => round($unemploymentInsurance, 2),
            'income_tax' => round($incomeTax, 2),
            'total' => round($totalInsurance + $incomeTax + ($options['other_deductions'] ?? 0), 2),
            'breakdown' => $breakdown,
            'tax_details' => $taxDetails,
        ];
    }

    /**
     * Calculate progressive income tax based on Vietnam tax brackets
     *
     * Tax brackets (monthly):
     * 0 - 5M: 5%
     * 5M - 10M: 10%
     * 10M - 18M: 15%
     * 18M - 32M: 20%
     * 32M - 52M: 25%
     * 52M - 80M: 30%
     * > 80M: 35%
     */
    protected function calculateProgressiveTax(float $taxableIncome): float
    {
        $brackets = [
            ['limit' => 5000000, 'rate' => 0.05],
            ['limit' => 10000000, 'rate' => 0.10],
            ['limit' => 18000000, 'rate' => 0.15],
            ['limit' => 32000000, 'rate' => 0.20],
            ['limit' => 52000000, 'rate' => 0.25],
            ['limit' => 80000000, 'rate' => 0.30],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35],
        ];

        $tax = 0;
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            if ($taxableIncome <= $previousLimit) {
                break;
            }

            $taxableAtThisLevel = min($taxableIncome, $bracket['limit']) - $previousLimit;
            $tax += $taxableAtThisLevel * $bracket['rate'];
            $previousLimit = $bracket['limit'];
        }

        return $tax;
    }

    /**
     * Get number of dependents for tax calculation
     * Can be enhanced to read from employee_relatives table
     */
    protected function getDependentsCount(Employee $employee): int
    {
        // TODO: Calculate from employee_relatives where relationship type is dependent
        // For now, return 0 (can be passed in options)
        return 0;
    }

    /**
     * Calculate payroll for all active employees in a period
     */
    public function calculatePayrollForPeriod(PayrollPeriod $period, array $options = []): array
    {
        $employees = Employee::where('status', 'ACTIVE')->get();
        $results = [
            'success' => [],
            'failed' => [],
            'total' => $employees->count(),
        ];

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                try {
                    $this->calculatePayroll($employee, $period, $options[$employee->id] ?? []);
                    $results['success'][] = $employee->id;
                } catch (\Exception $e) {
                    Log::error("Failed to calculate payroll for employee {$employee->id}: {$e->getMessage()}");
                    $results['failed'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Update period status to PROCESSING
            $period->update(['status' => PayrollPeriod::STATUS_PROCESSING]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to calculate payroll for period: {$e->getMessage()}");
            throw $e;
        }

        return $results;
    }

    /**
     * Recalculate payroll for a specific employee (e.g., after adjustments)
     */
    public function recalculatePayroll(PayrollItem $payrollItem, array $options = []): PayrollItem
    {
        $employee = $payrollItem->employee;
        $period = $payrollItem->payrollPeriod;

        return $this->calculatePayroll($employee, $period, $options);
    }
}
