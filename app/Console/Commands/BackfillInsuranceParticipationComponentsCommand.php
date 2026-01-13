<?php

namespace App\Console\Commands;

use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInsuranceParticipationComponentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:backfill-components
                            {--dry-run : Run in dry-run mode without making changes}
                            {--employee= : Backfill for specific employee UUID}
                            {--limit= : Limit number of participations to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill insurance participation components from 3 boolean fields to 5-component architecture';

    /**
     * Execute the console command.
     *
     * Quy tắc mapping:
     * - has_social_insurance = true → Tạo 3 components: RETIREMENT_SURVIVOR, SICKNESS_MATERNITY, OCC_ACCIDENT_DISEASE
     * - has_health_insurance = true → Tạo HEALTH component
     * - has_unemployment_insurance = true → Tạo UNEMPLOYMENT component
     *
     * Lưu ý:
     * - Chạy idempotent: Không tạo duplicate nếu component đã tồn tại
     * - Mỗi component được tạo với is_enabled = true, base_type = INSURANCE_SALARY, rate = default rate
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $employeeUuid = $this->option('employee');
        $limit = $this->option('limit');

        if ($dryRun) {
            $this->info('🔍 Running in DRY-RUN mode. No changes will be made.');
        }

        // Load all active components
        $components = InsuranceComponent::active()->get()->keyBy('code');

        if ($components->count() < 5) {
            $this->error('❌ Error: Not all 5 insurance components are seeded. Run InsuranceComponentSeeder first.');
            return self::FAILURE;
        }

        // Query participations
        $query = InsuranceParticipation::query()
            ->with('components') // Để check duplicate
            ->orderBy('created_at');

        if ($employeeUuid) {
            $query->where('employee_id', $employeeUuid);
            $this->info("🎯 Filtering for employee: {$employeeUuid}");
        }

        if ($limit) {
            $query->limit((int) $limit);
            $this->info("📊 Processing limit: {$limit} participations");
        }

        $participations = $query->get();
        $totalCount = $participations->count();

        if ($totalCount === 0) {
            $this->warn('⚠️  No insurance participations found to backfill.');
            return self::SUCCESS;
        }

        $this->info("📋 Found {$totalCount} participations to process.");
        $this->newLine();

        $processedCount = 0;
        $skippedCount = 0;
        $createdComponentsCount = 0;

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        foreach ($participations as $participation) {
            $componentCodesToCreate = $this->determineComponentsForParticipation($participation);

            // Check existing components by code
            $existingComponentCodes = $participation->components
                ->pluck('component.code')
                ->toArray();

            $newComponentCodes = array_diff($componentCodesToCreate, $existingComponentCodes);

            if (empty($newComponentCodes)) {
                $skippedCount++;
                $progressBar->advance();
                continue; // Đã có đủ components rồi, skip
            }

            if (!$dryRun) {
                DB::transaction(function () use ($participation, $newComponentCodes, $components, &$createdComponentsCount) {
                    foreach ($newComponentCodes as $componentCode) {
                        $component = $components->get($componentCode);

                        InsuranceParticipationComponent::create([
                            'insurance_participation_id' => $participation->id,
                            'component_id' => $component->id,
                            'is_enabled' => true,
                            'rate_total' => $component->default_rate_total,
                            'base_type' => 'INSURANCE_SALARY', // Default, có thể update sau cho BHTN
                            'base_amount' => null,
                            'note' => 'Backfilled from legacy boolean fields',
                        ]);

                        $createdComponentsCount++;
                    }
                });
            } else {
                // Dry run: Chỉ đếm
                $createdComponentsCount += count($newComponentCodes);
            }

            $processedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Backfill completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Participations', $totalCount],
                ['Processed', $processedCount],
                ['Skipped (already have components)', $skippedCount],
                ['Components Created', $createdComponentsCount],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY-RUN. No actual changes were made.');
            $this->info('💡 Remove --dry-run flag to apply changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Determine which component codes should be created for a participation
     * based on its boolean fields.
     *
     * @param InsuranceParticipation $participation
     * @return array Component codes
     */
    private function determineComponentsForParticipation(InsuranceParticipation $participation): array
    {
        $components = [];

        // has_social_insurance = true → 3 components
        if ($participation->has_social_insurance) {
            $components[] = 'RETIREMENT_SURVIVOR';
            $components[] = 'SICKNESS_MATERNITY';
            $components[] = 'OCC_ACCIDENT_DISEASE';
        }

        // has_health_insurance = true → HEALTH
        if ($participation->has_health_insurance) {
            $components[] = 'HEALTH';
        }

        // has_unemployment_insurance = true → UNEMPLOYMENT
        if ($participation->has_unemployment_insurance) {
            $components[] = 'UNEMPLOYMENT';
        }

        return $components;
    }
}
