<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'leave:initialize-balances',
    description: 'Initialize leave balances for all employees or a specific employee'
)]
class InitializeLeaveBalances extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(?int $year = null, ?string $employee = null)
    {
        $year = $year ?? now()->year;
        $employeeId = $employee;

        $this->info("Initializing leave balances for year {$year}...");

        DB::beginTransaction();
        try {
            $employees = $employeeId
                ? Employee::where('id', $employeeId)->get()
                : Employee::whereHas('contracts', function ($query) {
                    $query->where('status', 'ACTIVE');
                })->get();

            if ($employees->isEmpty()) {
                $this->warn('No active employees found!');
                return 1;
            }

            $leaveTypes = LeaveType::where('requires_approval', true)->get();

            $progressBar = $this->output->createProgressBar($employees->count());
            $created = 0;
            $skipped = 0;

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    // Check if balance already exists
                    $exists = LeaveBalance::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $year)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Calculate total days based on contract type and seniority
                    $totalDays = $this->calculateTotalDays($employee, $leaveType, $year);

                    // Calculate carried forward from previous year
                    $carriedForward = 0;
                    if ($year > now()->year || ($year == now()->year && now()->month <= 3)) {
                        $carriedForward = $this->calculateCarriedForward($employee, $leaveType, $year - 1);
                    }

                    LeaveBalance::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                        'total_days' => $totalDays,
                        'used_days' => 0,
                        'remaining_days' => $totalDays + $carriedForward,
                        'carried_forward' => $carriedForward,
                    ]);

                    $created++;
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info("✓ Created {$created} leave balance records");
            $this->info("✓ Skipped {$skipped} existing records");
            $this->info('Leave balances initialized successfully!');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to initialize leave balances: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Calculate total days based on contract type and seniority
     */
    private function calculateTotalDays(Employee $employee, LeaveType $leaveType, int $year): float
    {
        // Get active contract
        $contract = $employee->contracts()
            ->where('status', 'ACTIVE')
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$contract) {
            return 0;
        }

        // Base days from leave type
        $baseDays = $leaveType->days_per_year;

        // Probation contract = no annual leave
        if ($contract->contract_type === 'PROBATION') {
            return 0;
        }

        // Official contract: 1 day per month = 12 days per year
        if ($contract->contract_type === 'OFFICIAL' && $leaveType->code === 'ANNUAL') {
            $baseDays = 12; // 1 day/month
        }

        // Calculate seniority bonus: +1 day per 5 years
        if ($leaveType->code === 'ANNUAL') {
            $seniorityYears = $this->calculateSeniorityYears($employee, $year);
            $seniorityBonus = floor($seniorityYears / 5);
            $baseDays += $seniorityBonus;
        }

        return $baseDays;
    }

    /**
     * Calculate seniority years
     */
    private function calculateSeniorityYears(Employee $employee, int $year): int
    {
        // Get first contract start date
        $firstContract = $employee->contracts()
            ->orderBy('start_date', 'asc')
            ->first();

        if (!$firstContract) {
            return 0;
        }

        $startDate = $firstContract->start_date;
        $endDate = "{$year}-12-31";

        return \Carbon\Carbon::parse($startDate)->diffInYears($endDate);
    }

    /**
     * Calculate carried forward days from previous year
     */
    private function calculateCarriedForward(Employee $employee, LeaveType $leaveType, int $previousYear): float
    {
        $previousBalance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $previousYear)
            ->first();

        if (!$previousBalance) {
            return 0;
        }

        // Only carry forward remaining annual leave
        if ($leaveType->code !== 'ANNUAL') {
            return 0;
        }

        // Carried forward = remaining days from previous year (can use until Q1 next year)
        return max(0, $previousBalance->remaining_days);
    }
}
