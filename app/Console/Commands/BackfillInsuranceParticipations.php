<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInsuranceParticipations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:backfill-participations
                            {--dry-run : Show what would be created without actually creating}
                            {--chunk=100 : Number of contracts to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill insurance participations for existing active contracts without participation records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $this->info('🔍 Scanning active contracts...');

        // Find contracts without insurance participation
        $contractsQuery = Contract::query()
            ->where('status', 'ACTIVE')
            ->whereDoesntHave('insuranceParticipation')
            ->whereNotNull('insurance_salary') // Only backfill if insurance salary is set
            ->where('insurance_salary', '>', 0);

        $totalContracts = $contractsQuery->count();

        if ($totalContracts === 0) {
            $this->info('✅ No contracts need backfilling. All active contracts already have insurance participations.');
            return 0;
        }

        $this->info("📊 Found {$totalContracts} contracts without insurance participation.");

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be created. Showing first 10 contracts:');
            $this->newLine();

            $sampleContracts = $contractsQuery->with('employee')->limit(10)->get();

            $this->table(
                ['Contract ID', 'Employee Code', 'Employee Name', 'Insurance Salary', 'Start Date'],
                $sampleContracts->map(fn($c) => [
                    substr($c->id, 0, 8) . '...',
                    $c->employee->code ?? 'N/A',
                    $c->employee->name ?? 'N/A',
                    number_format($c->insurance_salary) . ' VND',
                    $c->start_date->format('d/m/Y'),
                ])
            );

            $this->newLine();
            $this->info("💡 Total contracts to backfill: {$totalContracts}");
            $this->info('💡 Run without --dry-run to actually create participations.');

            return 0;
        }

        if (!$this->confirm("📝 Create insurance participations for {$totalContracts} contracts?", true)) {
            $this->warn('❌ Operation aborted by user.');
            return 1;
        }

        // Get default insurance components
        $defaultComponents = InsuranceComponent::where('is_enabled', true)
            ->orderBy('display_order')
            ->get();

        if ($defaultComponents->isEmpty()) {
            $this->error('❌ No enabled insurance components found! Please run the seeder first:');
            $this->error('   php artisan db:seed --class=InsuranceComponentSeeder');
            return 1;
        }

        $this->info('📋 Using insurance components:');
        foreach ($defaultComponents as $component) {
            $totalRate = $component->employee_rate + $component->employer_rate;
            $this->info("   • {$component->name}: {$totalRate}% (NLĐ: {$component->employee_rate}%, NSDLĐ: {$component->employer_rate}%)");
        }
        $this->newLine();

        $this->info('🚀 Creating insurance participations...');
        $bar = $this->output->createProgressBar($totalContracts);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        $created = 0;
        $errors = 0;
        $errorDetails = [];

        $contractsQuery->with('employee')->chunk($chunkSize, function ($contracts) use ($defaultComponents, $bar, &$created, &$errors, &$errorDetails) {
            foreach ($contracts as $contract) {
                try {
                    DB::transaction(function () use ($contract, $defaultComponents, &$created) {
                        // Calculate total rate
                        $totalRate = $defaultComponents->sum(fn($c) => $c->employee_rate + $c->employer_rate);

                        // Create participation
                        $participation = InsuranceParticipation::create([
                            'contract_id' => $contract->id,
                            'insurance_salary' => $contract->insurance_salary,
                            'status' => 'ACTIVE',
                            'started_at' => $contract->start_date,
                            'terminated_at' => null,
                            'total_rate' => $totalRate,
                        ]);

                        // Attach default components with their current rates
                        foreach ($defaultComponents as $component) {
                            $participation->components()->create([
                                'component_id' => $component->id,
                                'employee_rate' => $component->employee_rate,
                                'employer_rate' => $component->employer_rate,
                                'is_fixed_amount' => false,
                                'fixed_amount' => null,
                            ]);
                        }

                        $created++;
                    });
                } catch (\Exception $e) {
                    $errors++;
                    $errorDetails[] = [
                        'contract_id' => $contract->id,
                        'employee_code' => $contract->employee->code ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    BACKFILL SUMMARY                       ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("✅ Successfully created: {$created} participations");

        if ($errors > 0) {
            $this->error("❌ Failed to create: {$errors} participations");
            $this->newLine();
            $this->error('Error details:');

            foreach (array_slice($errorDetails, 0, 5) as $error) {
                $this->error("   • Contract: {$error['contract_id']} (Employee: {$error['employee_code']})");
                $this->error("     Reason: {$error['error']}");
            }

            if (count($errorDetails) > 5) {
                $this->error('   ... and ' . (count($errorDetails) - 5) . ' more errors. Check logs for full details.');
            }
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Run integrity check
        if ($created > 0) {
            $this->info('🔍 Running integrity check to verify created data...');
            $this->newLine();
            $this->call('insurance:check-integrity');
        }

        return $errors > 0 ? 1 : 0;
    }
}
