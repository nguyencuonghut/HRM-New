<?php

namespace App\Console\Commands;

use App\Events\LeaveRequestEnded;
use App\Models\LeaveRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EndExpiredLeaves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:end-expired
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired leave requests as ended and update employee status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Checking for expired leave requests...');

        // Find all APPROVED leaves that have ended (end_date < today)
        $expiredLeaves = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('end_date', '<', now()->toDateString())
            ->with(['employee', 'leaveType'])
            ->get();

        if ($expiredLeaves->isEmpty()) {
            $this->info('✓ No expired leaves found.');
            return self::SUCCESS;
        }

        $this->info("Found {$expiredLeaves->count()} expired leave(s):");
        $this->newLine();

        $table = [];
        foreach ($expiredLeaves as $leave) {
            $table[] = [
                'ID' => substr($leave->id, 0, 8),
                'Employee' => $leave->employee->full_name,
                'Type' => $leave->leaveType->name,
                'End Date' => $leave->end_date->format('Y-m-d'),
                'Days' => $leave->days,
            ];
        }
        $this->table(['ID', 'Employee', 'Type', 'End Date', 'Days'], $table);

        if ($dryRun) {
            $this->warn('🔸 DRY RUN - No changes will be made');
            return self::SUCCESS;
        }

        if (!$this->confirm('Do you want to process these leaves?', true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $this->info('Processing...');
        $this->newLine();

        $processed = 0;
        $errors = 0;

        foreach ($expiredLeaves as $leave) {
            try {
                $this->line("Processing: {$leave->employee->full_name} ({$leave->leaveType->name})");

                // Dispatch event to trigger listeners
                // This will:
                // 1. End related EmployeeAbsence (EndEmployeeAbsenceOnLeaveEnded)
                // 2. Update employee status (UpdateEmployeeStatusOnLeaveEnded)
                event(new LeaveRequestEnded($leave));

                Log::info('Expired leave processed', [
                    'leave_request_id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'end_date' => $leave->end_date,
                ]);

                $processed++;
                $this->info("  ✓ Processed");
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error: {$e->getMessage()}");

                Log::error('Failed to process expired leave', [
                    'leave_request_id' => $leave->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("✓ Processed: {$processed}");
        if ($errors > 0) {
            $this->error("✗ Errors: {$errors}");
        }

        return self::SUCCESS;
    }
}
