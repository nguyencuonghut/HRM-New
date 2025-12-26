<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\EmployeeInsuranceProfile;
use App\Services\EmployeeInsuranceProfileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: Backfill insurance profiles từ legacy contracts
 * 
 * Usage:
 *   php artisan insurance:backfill-profiles              # Backfill all contracts
 *   php artisan insurance:backfill-profiles --dry-run    # Preview without changes
 *   php artisan insurance:backfill-profiles --employee=uuid  # Backfill for specific employee
 */
class BackfillInsuranceProfilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:backfill-profiles
                            {--dry-run : Preview changes without actually creating profiles}
                            {--employee= : Only process contracts for specific employee ID}
                            {--limit=100 : Maximum number of contracts to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill insurance profiles from legacy contracts';

    /**
     * @var EmployeeInsuranceProfileService
     */
    protected $insuranceProfileService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmployeeInsuranceProfileService $insuranceProfileService)
    {
        parent::__construct();
        $this->insuranceProfileService = $insuranceProfileService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $employeeId = $this->option('employee');
        $limit = (int) $this->option('limit');

        $this->info("Starting insurance profile backfill...");
        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }
        if ($employeeId) {
            $this->info("Processing employee: {$employeeId}");
        }
        $this->info("Limit: {$limit} contracts");
        $this->newLine();

        // Find contracts cần backfill:
        // - Contract ACTIVE/EXPIRED/CANCELLED có insurance_salary
        // - Chưa có profile cho khoảng thời gian này
        $query = Contract::whereNotNull('insurance_salary')
            ->whereNotNull('position_id')
            ->whereNotNull('employee_id')
            ->whereIn('status', ['ACTIVE', 'EXPIRED', 'CANCELLED', 'TERMINATED'])
            ->orderBy('employee_id')
            ->orderBy('start_date')
            ->limit($limit);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $contracts = $query->get();
        $this->info("Found {$contracts->count()} contracts to process");
        $this->newLine();

        $stats = [
            'total' => $contracts->count(),
            'created' => 0,
            'skipped_exists' => 0,
            'skipped_missing_data' => 0,
            'errors' => 0,
        ];

        $bar = $this->output->createProgressBar($stats['total']);
        $bar->start();

        foreach ($contracts as $contract) {
            try {
                // Check nếu đã có profile cho khoảng thời gian này
                $existingProfile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)
                    ->where('applied_from', $contract->start_date)
                    ->first();

                if ($existingProfile) {
                    $stats['skipped_exists']++;
                    $bar->advance();
                    continue;
                }

                // Validate required fields
                if (!$contract->insurance_salary || !$contract->position_id || !$contract->employee_id) {
                    $stats['skipped_missing_data']++;
                    $bar->advance();
                    continue;
                }

                // Create profile (unless dry-run)
                if (!$dryRun) {
                    $profile = $this->insuranceProfileService->backfillProfileFromLegacyContract($contract);
                    if ($profile) {
                        $stats['created']++;
                    } else {
                        $stats['skipped_missing_data']++;
                    }
                } else {
                    // Dry run: just count
                    $stats['created']++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Failed to backfill insurance profile", [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'error' => $e->getMessage(),
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info("📊 Backfill Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Contracts', $stats['total']],
                ['✅ Profiles Created', $stats['created']],
                ['⏭️ Skipped (Already Exists)', $stats['skipped_exists']],
                ['⏭️ Skipped (Missing Data)', $stats['skipped_missing_data']],
                ['❌ Errors', $stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a DRY RUN. Run without --dry-run to apply changes.");
        }

        if ($stats['errors'] > 0) {
            $this->error("Some profiles failed to backfill. Check logs for details.");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Backfill completed successfully!");
        return Command::SUCCESS;
    }
}
