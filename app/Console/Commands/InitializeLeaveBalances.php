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
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:initialize-balances
                            {year? : The year to initialize balances for (default: current year)}
                            {employee? : Specific employee ID to initialize}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;
        $employeeId = $this->argument('employee');

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
     * Pro-rata calculation: 1 day per month worked in the year
     */
    private function calculateTotalDays(Employee $employee, LeaveType $leaveType, int $year): float
    {
        // Get current employment period
        $employment = $employee->employments()
            ->where('is_current', true)
            ->first();

        if (!$employment) {
            return 0; // No active employment
        }

        // For non-ANNUAL leave types, use default days
        if ($leaveType->code !== 'ANNUAL') {
            return $leaveType->days_per_year ?? 0;
        }

        // Calculate working months in this specific year
        $yearStart = \Carbon\Carbon::create($year, 1, 1);
        $yearEnd = \Carbon\Carbon::create($year, 12, 31);

        // Determine actual work period within the year
        $workStart = $employment->start_date->year == $year
            ? $employment->start_date
            : $yearStart;

        $workEnd = $employment->end_date && $employment->end_date->year == $year
            ? $employment->end_date
            : $yearEnd;

        // Calculate full working months (include partial month as full month)
        $workingMonths = $workStart->diffInMonths($workEnd) + 1;

        // Ensure not exceed 12 months
        $workingMonths = min($workingMonths, 12);

        // ANNUAL leave: 1 day per month worked
        $baseDays = $workingMonths;

        // Add seniority bonus ONLY if worked full year (12 months)
        // Seniority bonus: +1 day per 5 years of service
        if ($workingMonths >= 12) {
            $seniorityYears = $this->calculateSeniorityYears($employee, $year - 1); // Use previous years for seniority
            $seniorityBonus = floor($seniorityYears / 5);
            $baseDays += $seniorityBonus;
        }

        return $baseDays;
    }

    /**
     * Calculate seniority years
     *
     * QUAN TRỌNG: Tính tổng thâm niên qua tất cả các employment periods
     * Nếu nhân viên nghỉ việc rồi quay lại, chỉ tính các khoảng thời gian thực tế làm việc
     */
    private function calculateSeniorityYears(Employee $employee, int $year): int
    {
        // Method 1: Nếu có employment records → tính chính xác
        $employments = $employee->employments()
            ->where('start_date', '<=', "{$year}-12-31")
            ->get();

        if ($employments->isNotEmpty()) {
            $totalYears = 0;

            foreach ($employments as $employment) {
                $start = $employment->start_date;
                $end = $employment->end_date ?? \Carbon\Carbon::parse("{$year}-12-31");

                // Chỉ tính đến hết năm hiện tại
                if ($end->year > $year) {
                    $end = \Carbon\Carbon::parse("{$year}-12-31");
                }

                $totalYears += $start->diffInYears($end);
            }

            return $totalYears;
        }

        // Method 2 (fallback): Nếu chưa có employment records → dùng first contract (legacy)
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
