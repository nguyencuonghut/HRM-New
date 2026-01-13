<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\InsuranceMonthlyReport;
use App\Services\InsuranceChangeDetectionService;
use App\Services\InsuranceSnapshotService;
use App\Services\InsuranceExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkInsuranceOperations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:benchmark
                            {--employees= : Number of employees to simulate (default: actual count)}
                            {--iterations=3 : Number of iterations for averaging}
                            {--export : Include Excel export in benchmark}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark insurance operations: report generation, finalization, export';

    protected $results = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('Insurance Module Performance Benchmark');
        $this->info('===========================================');
        $this->newLine();

        $employeeCount = $this->option('employees')
            ? (int)$this->option('employees')
            : Employee::count();

        $iterations = (int)$this->option('iterations');

        $this->info("Configuration:");
        $this->line("  Employees: {$employeeCount}");
        $this->line("  Iterations: {$iterations}");
        $this->line("  Include Export: " . ($this->option('export') ? 'Yes' : 'No'));
        $this->newLine();

        // Run benchmarks
        $this->benchmarkChangeDetection($employeeCount, $iterations);
        $this->benchmarkSnapshotCreation($iterations);

        if ($this->option('export')) {
            $this->benchmarkExcelExport($iterations);
        }

        $this->benchmarkDatabaseQueries();

        // Display results
        $this->displayResults();

        // Provide recommendations
        $this->provideRecommendations($employeeCount);

        return Command::SUCCESS;
    }

    /**
     * Benchmark change detection for monthly report
     */
    protected function benchmarkChangeDetection($employeeCount, $iterations)
    {
        $this->info('1. Benchmarking Change Detection (Report Generation)...');

        $times = [];
        $recordCounts = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            // Simulate change detection
            $year = date('Y');
            $month = date('m');

            // Count potential changes (without creating report)
            $increases = DB::table('contracts')
                ->where('status', 'APPROVED')
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->count();

            $decreases = DB::table('contracts')
                ->where('status', 'TERMINATED')
                ->whereYear('end_date', $year)
                ->whereMonth('end_date', $month)
                ->count();

            $adjustments = DB::table('insurance_participations')
                ->where('status', 'ACTIVE')
                ->whereYear('updated_at', $year)
                ->whereMonth('updated_at', $month)
                ->count();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $times[] = ($endTime - $startTime) * 1000; // Convert to ms
            $recordCounts[] = $increases + $decreases + $adjustments;

            usleep(100000); // 100ms pause between iterations
        }

        $avgTime = round(array_sum($times) / count($times), 2);
        $avgRecords = round(array_sum($recordCounts) / count($recordCounts));
        $throughput = $avgRecords > 0 ? round($avgRecords / ($avgTime / 1000), 2) : 0;

        $this->results['change_detection'] = [
            'avg_time' => $avgTime,
            'avg_records' => $avgRecords,
            'throughput' => $throughput,
            'status' => $avgTime < 500 ? 'EXCELLENT' : ($avgTime < 2000 ? 'GOOD' : 'NEEDS_OPTIMIZATION')
        ];

        $this->line("   Average time: {$avgTime} ms");
        $this->line("   Average records detected: {$avgRecords}");
        $this->line("   Throughput: {$throughput} records/sec");
    }

    /**
     * Benchmark snapshot creation (finalization)
     */
    protected function benchmarkSnapshotCreation($iterations)
    {
        $this->info('2. Benchmarking Snapshot Creation (Finalization)...');

        // Find a DRAFT report or create one for testing
        $report = InsuranceMonthlyReport::where('status', 'DRAFT')->first();

        if (!$report) {
            $this->warn('   No DRAFT report found. Creating test report...');
            $report = InsuranceMonthlyReport::create([
                'year' => date('Y'),
                'month' => date('m'),
                'status' => 'DRAFT',
                'created_by' => 1,
            ]);
        }

        $recordCount = $report->changeRecords()->count();

        if ($recordCount === 0) {
            $this->warn('   Report has no records. Skipping snapshot benchmark.');
            return;
        }

        $times = [];
        $contributionCounts = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Reset report status for re-testing
            $report->update(['status' => 'DRAFT']);

            // Delete existing snapshot data
            DB::table('insurance_monthly_contributions')
                ->where('report_id', $report->id)
                ->delete();

            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            try {
                // Run finalization
                $snapshotService = app(InsuranceSnapshotService::class);
                $snapshotService->createSnapshot($report);

                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);

                $contributionCount = DB::table('insurance_monthly_contributions')
                    ->where('report_id', $report->id)
                    ->count();

                $times[] = ($endTime - $startTime) * 1000;
                $contributionCounts[] = $contributionCount;

            } catch (\Exception $e) {
                $this->error("   Iteration {$i} failed: " . $e->getMessage());
                continue;
            }

            usleep(100000);
        }

        if (count($times) > 0) {
            $avgTime = round(array_sum($times) / count($times), 2);
            $avgContributions = round(array_sum($contributionCounts) / count($contributionCounts));
            $throughput = $avgContributions > 0 ? round($avgContributions / ($avgTime / 1000), 2) : 0;

            $this->results['snapshot_creation'] = [
                'avg_time' => $avgTime,
                'avg_contributions' => $avgContributions,
                'throughput' => $throughput,
                'status' => $avgTime < 5000 ? 'EXCELLENT' : ($avgTime < 15000 ? 'GOOD' : 'NEEDS_OPTIMIZATION')
            ];

            $this->line("   Average time: {$avgTime} ms");
            $this->line("   Average contributions created: {$avgContributions}");
            $this->line("   Throughput: {$throughput} contributions/sec");
        }
    }

    /**
     * Benchmark Excel export
     */
    protected function benchmarkExcelExport($iterations)
    {
        $this->info('3. Benchmarking Excel Export...');

        $report = InsuranceMonthlyReport::where('status', 'FINALIZED')->first();

        if (!$report) {
            $this->warn('   No FINALIZED report found. Skipping export benchmark.');
            return;
        }

        $times = [];
        $fileSizes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            try {
                $exportService = app(InsuranceExportService::class);
                $filePath = $exportService->exportToFile($report);

                $endTime = microtime(true);

                $times[] = ($endTime - $startTime) * 1000;

                if (\Storage::exists($filePath)) {
                    $fileSizes[] = \Storage::size($filePath) / 1024; // KB
                    \Storage::delete($filePath);
                }

            } catch (\Exception $e) {
                $this->error("   Iteration {$i} failed: " . $e->getMessage());
                continue;
            }

            usleep(100000);
        }

        if (count($times) > 0) {
            $avgTime = round(array_sum($times) / count($times), 2);
            $avgSize = count($fileSizes) > 0 ? round(array_sum($fileSizes) / count($fileSizes), 2) : 0;

            $this->results['excel_export'] = [
                'avg_time' => $avgTime,
                'avg_file_size' => $avgSize,
                'status' => $avgTime < 3000 ? 'EXCELLENT' : ($avgTime < 10000 ? 'GOOD' : 'NEEDS_OPTIMIZATION')
            ];

            $this->line("   Average time: {$avgTime} ms");
            $this->line("   Average file size: {$avgSize} KB");
        }
    }

    /**
     * Benchmark database query performance
     */
    protected function benchmarkDatabaseQueries()
    {
        $this->info('4. Benchmarking Database Queries...');

        $queries = [
            'Active Participations' => function () {
                return DB::table('insurance_participations')
                    ->where('status', 'ACTIVE')
                    ->count();
            },
            'Participation with Components' => function () {
                return DB::table('insurance_participations as ip')
                    ->join('insurance_participation_components as ipc', 'ip.id', '=', 'ipc.insurance_participation_id')
                    ->where('ip.status', 'ACTIVE')
                    ->select('ip.id')
                    ->distinct()
                    ->count();
            },
            'Monthly Reports (Last Year)' => function () {
                return DB::table('insurance_monthly_reports')
                    ->where('year', '>=', date('Y') - 1)
                    ->count();
            },
            'Recent Change Records' => function () {
                return DB::table('insurance_change_records')
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->count();
            },
        ];

        $queryResults = [];

        foreach ($queries as $name => $query) {
            $times = [];

            for ($i = 0; $i < 5; $i++) {
                $startTime = microtime(true);
                $result = $query();
                $endTime = microtime(true);

                $times[] = ($endTime - $startTime) * 1000;
            }

            $avgTime = round(array_sum($times) / count($times), 2);

            $queryResults[$name] = [
                'avg_time' => $avgTime,
                'status' => $avgTime < 50 ? 'EXCELLENT' : ($avgTime < 200 ? 'GOOD' : 'NEEDS_OPTIMIZATION')
            ];

            $this->line("   {$name}: {$avgTime} ms");
        }

        $this->results['database_queries'] = $queryResults;
    }

    /**
     * Display benchmark results summary
     */
    protected function displayResults()
    {
        $this->newLine();
        $this->info('===========================================');
        $this->info('Benchmark Results Summary');
        $this->info('===========================================');
        $this->newLine();

        foreach ($this->results as $operation => $data) {
            if (is_array($data) && !isset($data['avg_time'])) {
                // Database queries (nested)
                $this->line("<comment>" . ucwords(str_replace('_', ' ', $operation)) . "</comment>");
                foreach ($data as $queryName => $queryData) {
                    $status = $this->getStatusColor($queryData['status']);
                    $this->line("  {$queryName}: {$queryData['avg_time']} ms [{$status}]");
                }
            } else {
                $status = $this->getStatusColor($data['status']);
                $operationName = ucwords(str_replace('_', ' ', $operation));
                $this->line("<comment>{$operationName}</comment>");
                $this->line("  Time: {$data['avg_time']} ms [{$status}]");

                if (isset($data['throughput'])) {
                    $this->line("  Throughput: {$data['throughput']} records/sec");
                }
            }
            $this->newLine();
        }
    }

    /**
     * Get colored status text
     */
    protected function getStatusColor($status)
    {
        return match($status) {
            'EXCELLENT' => '<info>EXCELLENT</info>',
            'GOOD' => '<comment>GOOD</comment>',
            'NEEDS_OPTIMIZATION' => '<error>NEEDS OPTIMIZATION</error>',
            default => $status
        };
    }

    /**
     * Provide optimization recommendations
     */
    protected function provideRecommendations($employeeCount)
    {
        $this->info('===========================================');
        $this->info('Recommendations');
        $this->info('===========================================');
        $this->newLine();

        $needsOptimization = false;

        // Check change detection
        if (isset($this->results['change_detection']) && $this->results['change_detection']['status'] === 'NEEDS_OPTIMIZATION') {
            $needsOptimization = true;
            $this->warn('⚠ Change Detection is slow:');
            $this->line('  • Add indexes on contracts(status, start_date, end_date)');
            $this->line('  • Add indexes on insurance_participations(status, updated_at)');
            $this->line('  • Consider caching recent changes');
            $this->newLine();
        }

        // Check snapshot creation
        if (isset($this->results['snapshot_creation']) && $this->results['snapshot_creation']['status'] === 'NEEDS_OPTIMIZATION') {
            $needsOptimization = true;
            $this->warn('⚠ Snapshot Creation is slow:');
            $this->line('  • Use chunking for large datasets (chunk size: 100-500)');
            $this->line('  • Consider using DB transactions with smaller batches');
            $this->line('  • Run finalization as background job for large reports');
            $this->newLine();
        }

        // Check export
        if (isset($this->results['excel_export']) && $this->results['excel_export']['status'] === 'NEEDS_OPTIMIZATION') {
            $needsOptimization = true;
            $this->warn('⚠ Excel Export is slow:');
            $this->line('  • Use streaming export for large files');
            $this->line('  • Consider queue-based export with notification');
            $this->line('  • Optimize cell formatting and formulas');
            $this->newLine();
        }

        // Scale recommendations
        if ($employeeCount > 1000) {
            $this->comment('📈 Large Scale Deployment:');
            $this->line('  • Enable query caching for common lookups');
            $this->line('  • Use read replicas for report queries');
            $this->line('  • Schedule finalization during off-peak hours');
            $this->line('  • Monitor memory usage during snapshot creation');
            $this->newLine();
        }

        if (!$needsOptimization) {
            $this->info('✓ All operations are performing well!');
            $this->line('  No immediate optimizations needed.');
            $this->newLine();
        }

        // General best practices
        $this->comment('💡 Best Practices:');
        $this->line('  • Run integrity checks before month-end reports');
        $this->line('  • Archive old reports (> 2 years) to separate table');
        $this->line('  • Set up monitoring alerts for slow operations (> 30s)');
        $this->line('  • Test with peak load scenarios periodically');
    }
}
