<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\Employee;
use App\Models\EmployeeAbsence;
use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceParticipation;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceCalculationService
{
    /**
     * Detect all changes (INCREASE/DECREASE/ADJUST) for a given month
     *
     * @param int $year
     * @param int $month
     * @return array ['increase' => Collection, 'decrease' => Collection, 'adjust' => Collection]
     */
    public function calculateMonthlyChanges(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return [
            'increase' => $this->detectIncrease($year, $month, $startDate, $endDate),
            'decrease' => $this->detectDecrease($year, $month, $startDate, $endDate),
            'adjust' => $this->detectAdjustment($year, $month, $startDate, $endDate),
        ];
    }

    /**
     * Detect INCREASE (TĂNG): New hires + Return to work
     */
    protected function detectIncrease(int $year, int $month, Carbon $startDate, Carbon $endDate): Collection
    {
        $records = collect();

        // 1. NEW_HIRE: Employees hired in this month
        $newHires = Employee::where('status', 'ACTIVE')
            ->whereBetween('hire_date', [$startDate, $endDate])
            ->whereDoesntHave('insuranceParticipations', function ($query) {
                $query->where('status', InsuranceParticipation::STATUS_ACTIVE);
            })
            ->get();

        foreach ($newHires as $employee) {
            $salary = $this->getInsuranceSalary($employee, $startDate);
            if ($salary > 0) {
                $records->push([
                    'employee' => $employee,
                    'change_type' => InsuranceChangeRecord::TYPE_INCREASE,
                    'auto_reason' => InsuranceChangeRecord::REASON_NEW_HIRE,
                    'insurance_salary' => $salary,
                    'effective_date' => $employee->hire_date,
                    'system_notes' => "Nhân viên mới vào làm ngày " . $employee->hire_date->format('d/m/Y'),
                ]);
            }
        }

        // 2. RETURN_TO_WORK: Ended long absences in this month
        $returnedEmployees = EmployeeAbsence::where('status', EmployeeAbsence::STATUS_ENDED)
            ->where('affects_insurance', true)
            ->whereBetween('end_date', [$startDate, $endDate])
            ->with('employee')
            ->get();

        foreach ($returnedEmployees as $absence) {
            $employee = $absence->employee;
            if ($employee && $employee->status === 'ACTIVE') {
                $salary = $this->getInsuranceSalary($employee, Carbon::parse($absence->end_date));
                if ($salary > 0) {
                    $records->push([
                        'employee' => $employee,
                        'change_type' => InsuranceChangeRecord::TYPE_INCREASE,
                        'auto_reason' => InsuranceChangeRecord::REASON_RETURN_TO_WORK,
                        'insurance_salary' => $salary,
                        'effective_date' => $absence->end_date,
                        'system_notes' => "Quay lại làm việc sau nghỉ " . $absence->getTypeLabel(),
                        'leave_request_id' => $absence->leave_request_id,
                    ]);
                }
            }
        }

        return $records;
    }

    /**
     * Detect DECREASE (GIẢM): Terminations + Long absences
     */
    protected function detectDecrease(int $year, int $month, Carbon $startDate, Carbon $endDate): Collection
    {
        $records = collect();

        // 1. TERMINATION: Employees terminated in this month
        $terminated = Employee::where('status', 'TERMINATED')
            ->whereHas('insuranceParticipations', function ($query) use ($startDate, $endDate) {
                $query->where('status', InsuranceParticipation::STATUS_ACTIVE)
                    ->whereBetween('participation_end_date', [$startDate, $endDate]);
            })
            ->with(['insuranceParticipations' => function ($query) {
                $query->where('status', InsuranceParticipation::STATUS_ACTIVE)->latest();
            }])
            ->get();

        foreach ($terminated as $employee) {
            $participation = $employee->insuranceParticipations->first();
            if ($participation) {
                $records->push([
                    'employee' => $employee,
                    'change_type' => InsuranceChangeRecord::TYPE_DECREASE,
                    'auto_reason' => InsuranceChangeRecord::REASON_TERMINATION,
                    'insurance_salary' => $participation->insurance_salary,
                    'effective_date' => $participation->participation_end_date,
                    'system_notes' => "Nghỉ việc ngày " . $participation->participation_end_date->format('d/m/Y'),
                ]);
            }
        }

        // 2. LONG_ABSENCE: Leave requests >30 days starting in this month
        // 2a. MATERNITY: Immediate decrease
        $maternityLeaves = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', function ($query) {
                $query->where('code', 'MATERNITY');
            })
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('employee')
            ->get();

        foreach ($maternityLeaves as $leave) {
            $employee = $leave->employee;
            $salary = $this->getInsuranceSalary($employee, Carbon::parse($leave->start_date));
            $records->push([
                'employee' => $employee,
                'change_type' => InsuranceChangeRecord::TYPE_DECREASE,
                'auto_reason' => InsuranceChangeRecord::REASON_LONG_ABSENCE,
                'insurance_salary' => $salary,
                'effective_date' => $leave->start_date,
                'system_notes' => "Nghỉ thai sản từ " . Carbon::parse($leave->start_date)->format('d/m/Y'),
                'leave_request_id' => $leave->id,
            ]);
        }

        // 2b. SICK/UNPAID >30 days
        $longLeaves = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', function ($query) {
                $query->whereIn('code', ['SICK', 'UNPAID']);
            })
            ->where('days', '>', 30)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('employee', 'leaveType')
            ->get();

        foreach ($longLeaves as $leave) {
            $employee = $leave->employee;
            $salary = $this->getInsuranceSalary($employee, Carbon::parse($leave->start_date));
            $records->push([
                'employee' => $employee,
                'change_type' => InsuranceChangeRecord::TYPE_DECREASE,
                'auto_reason' => InsuranceChangeRecord::REASON_LONG_ABSENCE,
                'insurance_salary' => $salary,
                'effective_date' => $leave->start_date,
                'system_notes' => "Nghỉ {$leave->leaveType->name} dài hạn ({$leave->days} ngày) từ " . Carbon::parse($leave->start_date)->format('d/m/Y'),
                'leave_request_id' => $leave->id,
            ]);
        }

        return $records;
    }

    /**
     * Detect ADJUST (ĐIỀU CHỈNH): Salary changes from contract appendices
     */
    protected function detectAdjustment(int $year, int $month, Carbon $startDate, Carbon $endDate): Collection
    {
        $records = collect();

        // Find contract appendices with insurance_salary changes effective in this month
        $appendices = ContractAppendix::whereHas('contract.employee', function ($query) {
            $query->where('status', 'ACTIVE');
        })
            ->whereBetween('effective_date', [$startDate, $endDate])
            ->whereNotNull('insurance_salary')
            ->with(['contract.employee', 'contract'])
            ->get();

        foreach ($appendices as $appendix) {
            $employee = $appendix->contract->employee;

            // Compare with previous salary
            $previousSalary = $appendix->contract->insurance_salary;
            $newSalary = $appendix->insurance_salary;

            if ($newSalary != $previousSalary) {
                $records->push([
                    'employee' => $employee,
                    'change_type' => InsuranceChangeRecord::TYPE_ADJUST,
                    'auto_reason' => InsuranceChangeRecord::REASON_SALARY_CHANGE,
                    'insurance_salary' => $newSalary,
                    'effective_date' => $appendix->effective_date,
                    'system_notes' => "Điều chỉnh lương BH từ " . number_format($previousSalary, 0, ',', '.') . " VNĐ sang " . number_format($newSalary, 0, ',', '.') . " VNĐ",
                    'contract_appendix_id' => $appendix->id,
                    'contract_id' => $appendix->contract_id,
                ]);
            }
        }

        return $records;
    }

    /**
     * Get insurance salary for an employee at a specific date
     * Priority: Contract Appendix > Contract > 0
     */
    public function getInsuranceSalary(Employee $employee, Carbon $date): float
    {
        // Find active contract at the date
        $contract = Contract::where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->first();

        if (!$contract) {
            return 0;
        }

        // Check if there's any appendix with insurance_salary at this date
        $appendix = ContractAppendix::where('contract_id', $contract->id)
            ->whereNotNull('insurance_salary')
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if ($appendix) {
            return (float) $appendix->insurance_salary;
        }

        // Fallback to contract insurance_salary
        return (float) ($contract->insurance_salary ?? 0);
    }

    /**
     * Check if employee has long absence (>30 days) in a month
     */
    public function checkLongAbsence(Employee $employee, int $year, int $month): ?LeaveRequest
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', function ($query) {
                $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->where('days', '>', 30)
            ->first();
    }
}
