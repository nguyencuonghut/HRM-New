<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class NotifyExpiringContracts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contracts:notify-expiring
                            {--days=30 : Number of days threshold}
                            {--all : Send for all contracts within threshold, not just milestones}
                            {--test : Test mode - only show contracts without sending notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications for contracts expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');
        $testMode = $this->option('test');
        $allMode = $this->option('all');

        $this->info("Checking for contracts expiring within {$daysThreshold} days...");

        $totalNotified = 0;

        if ($allMode) {
            // Mode: Gửi cho TẤT CẢ hợp đồng sắp hết hạn trong khoảng threshold
            $this->info("Mode: Sending for ALL contracts within {$daysThreshold} days");

            $endDateThreshold = now()->addDays($daysThreshold)->endOfDay();

            $contracts = Contract::where('status', 'ACTIVE')
                ->whereNotNull('end_date')
                ->where('end_date', '>=', now()->startOfDay())
                ->where('end_date', '<=', $endDateThreshold)
                ->with(['employee', 'department', 'position', 'created_by_user'])
                ->orderBy('end_date', 'asc')
                ->get();

            if ($contracts->isEmpty()) {
                $this->warn("No contracts found expiring within {$daysThreshold} days.");
                return Command::SUCCESS;
            }

            $this->info("Found {$contracts->count()} contract(s) expiring within {$daysThreshold} days:");

            foreach ($contracts as $contract) {
                $employee = $contract->employee;
                $daysRemaining = now()->startOfDay()->diffInDays($contract->end_date->startOfDay(), false);

                $this->line("  - {$contract->contract_number} ({$employee->full_name}) - Expires: {$contract->end_date->format('d/m/Y')} ({$daysRemaining} days)");

                if ($testMode) {
                    continue;
                }

                // Gửi thông báo
                $this->sendExpiringNotification($contract, $daysRemaining);
                $totalNotified++;
            }
        } else {
            // Mode: Chỉ gửi cho các milestone 30, 15, 7 ngày (để tránh spam)
            $this->info("Mode: Sending for milestone days only (30, 15, 7 days)");

            $notificationThresholds = [30, 15, 7];

            foreach ($notificationThresholds as $threshold) {
                if ($threshold > $daysThreshold) {
                    continue;
                }

                $targetDate = now()->addDays($threshold)->startOfDay();

                // Tìm hợp đồng hết hạn đúng vào ngày targetDate
                $contracts = Contract::where('status', 'ACTIVE')
                    ->whereNotNull('end_date')
                    ->whereDate('end_date', $targetDate)
                    ->with(['employee', 'department', 'position', 'created_by_user'])
                    ->get();

                if ($contracts->isEmpty()) {
                    continue;
                }

                $this->info("\nFound {$contracts->count()} contract(s) expiring in exactly {$threshold} days:");

                foreach ($contracts as $contract) {
                    $employee = $contract->employee;

                    $this->line("  - {$contract->contract_number} ({$employee->full_name}) - Expires: {$contract->end_date->format('d/m/Y')}");

                    if ($testMode) {
                        continue;
                    }

                    // Gửi thông báo
                    $this->sendExpiringNotification($contract, $threshold);
                    $totalNotified++;
                }
            }
        }

        if ($testMode) {
            $this->info("\nTest mode: No notifications were sent.");
        } else {
            $this->info("\nTotal notifications sent: {$totalNotified}");
        }

        return Command::SUCCESS;
    }

    /**
     * Gửi thông báo hợp đồng sắp hết hạn
     */
    private function sendExpiringNotification(Contract $contract, int $daysUntilExpiry): void
    {
        $employee = $contract->employee;
        $sentEmails = [];

        // 1. Gửi cho nhân viên (recipientType = 'employee')
        $employeeNotification = new ContractExpiringNotification($contract, $daysUntilExpiry, 'employee');

        if ($employee->personal_email && !in_array($employee->personal_email, $sentEmails)) {
            Notification::route('mail', $employee->personal_email)->notify($employeeNotification);
            $sentEmails[] = $employee->personal_email;
        }

        if ($employee->company_email && !in_array($employee->company_email, $sentEmails)) {
            Notification::route('mail', $employee->company_email)->notify($employeeNotification);
            $sentEmails[] = $employee->company_email;
        }

        if ($employee->user) {
            $employee->user->notify($employeeNotification);
        }

        // 2. Gửi cho người tạo hợp đồng (recipientType = 'manager')
        if ($contract->created_by) {
            $creator = \App\Models\User::find($contract->created_by);
            if ($creator && !in_array($creator->email, $sentEmails)) {
                $managerNotification = new ContractExpiringNotification($contract, $daysUntilExpiry, 'manager');
                $creator->notify($managerNotification);
                $sentEmails[] = $creator->email;
            }
        }

        // 3. Gửi cho người phê duyệt (recipientType = 'manager')
        if ($contract->approver_id) {
            $approver = \App\Models\User::find($contract->approver_id);
            if ($approver && !in_array($approver->email, $sentEmails)) {
                $managerNotification = new ContractExpiringNotification($contract, $daysUntilExpiry, 'manager');
                $approver->notify($managerNotification);
                $sentEmails[] = $approver->email;
            }
        }

        // 4. Gửi cho HEAD phòng/ban
        if ($contract->department_id) {
            $department = \App\Models\Department::find($contract->department_id);

            if ($department) {
                $departmentIds = [$department->id];

                // Thu thập parent departments
                if ($department->parent_id) {
                    $currentDept = $department;
                    while ($currentDept->parent_id) {
                        $parentDept = \App\Models\Department::find($currentDept->parent_id);
                        if ($parentDept) {
                            $departmentIds[] = $parentDept->id;
                            $currentDept = $parentDept;
                        } else {
                            break;
                        }
                    }
                }

                // Tìm HEADs
                $headAssignments = \App\Models\EmployeeAssignment::whereIn('department_id', $departmentIds)
                    ->where('role_type', 'HEAD')
                    ->where('status', 'ACTIVE')
                    ->whereDate('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>=', now());
                    })
                    ->with('employee')
                    ->get();

                foreach ($headAssignments as $headAssignment) {
                    if ($headAssignment->employee) {
                        $headEmployee = $headAssignment->employee;

                        // Create notification for HEAD with recipientType = 'head' and recipientName
                        $headNotification = new ContractExpiringNotification(
                            $contract,
                            $daysUntilExpiry,
                            'head',
                            $headEmployee->full_name
                        );

                        if ($headEmployee->company_email && !in_array($headEmployee->company_email, $sentEmails)) {
                            Notification::route('mail', $headEmployee->company_email)->notify($headNotification);
                            $sentEmails[] = $headEmployee->company_email;
                        }

                        if ($headEmployee->user) {
                            $headUserEmail = $headEmployee->user->email;
                            if (!in_array($headUserEmail, $sentEmails)) {
                                $headEmployee->user->notify($headNotification);
                                $sentEmails[] = $headUserEmail;
                            }
                        }
                    }
                }
            }
        }
    }
}
