<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckInsuranceIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:check-integrity
                            {--fix : Automatically fix issues where possible}
                            {--detailed : Show detailed information for each issue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check insurance data integrity: orphaned participations, missing components, invalid rates';

    protected $issues = [];
    protected $fixable = [];
    protected $fixed = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('Insurance Data Integrity Check');
        $this->info('===========================================');
        $this->newLine();

        $startTime = microtime(true);

        // Run all checks
        $this->checkOrphanedParticipations();
        $this->checkMissingComponents();
        $this->checkInvalidRateTotals();
        $this->checkInactiveComponentReferences();
        $this->checkDuplicateActiveParticipations();
        $this->checkMissingInsuranceSalary();

        $duration = round(microtime(true) - $startTime, 2);

        // Display summary
        $this->newLine();
        $this->info('===========================================');
        $this->info('Summary');
        $this->info('===========================================');
        $this->line("Execution time: {$duration}s");
        $this->line('Total issues found: ' . count($this->issues));

        if ($this->option('fix')) {
            $this->line('Issues fixed: ' . $this->fixed);
            $this->line('Issues remaining: ' . (count($this->issues) - $this->fixed));
        } else {
            $this->line('Fixable issues: ' . count($this->fixable));
            $this->newLine();
            if (count($this->fixable) > 0) {
                $this->comment('Run with --fix option to automatically fix ' . count($this->fixable) . ' issues');
            }
        }

        // Display issues
        if (count($this->issues) > 0) {
            $this->newLine();
            $this->warn('Issues Details:');
            foreach ($this->issues as $issue) {
                $icon = $issue['fixed'] ? '✓' : ($issue['fixable'] ? '⚠' : '✗');
                $status = $issue['fixed'] ? '<info>FIXED</info>' : ($issue['fixable'] ? '<comment>FIXABLE</comment>' : '<error>MANUAL</error>');
                $this->line("{$icon} [{$status}] {$issue['type']}: {$issue['message']}");

                if ($this->option('detailed') && isset($issue['details'])) {
                    $this->line('   Details: ' . $issue['details']);
                }
            }
        } else {
            $this->newLine();
            $this->info('✓ No integrity issues found. Insurance data is healthy!');
        }

        return count($this->issues) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Check for orphaned participations (no active contract)
     */
    protected function checkOrphanedParticipations()
    {
        $this->info('1. Checking for orphaned participations...');

        $orphaned = InsuranceParticipation::where('status', 'ACTIVE')
            ->whereDoesntHave('employee', function ($query) {
                $query->whereHas('contracts', function ($q) {
                    $q->where('status', 'APPROVED');
                });
            })
            ->with('employee')
            ->get();

        if ($orphaned->count() > 0) {
            foreach ($orphaned as $participation) {
                $employeeName = $participation->employee
                    ? "{$participation->employee->full_name} (ID: {$participation->employee_id})"
                    : "Employee ID: {$participation->employee_id}";

                $issue = [
                    'type' => 'Orphaned Participation',
                    'message' => "Participation #{$participation->id} for {$employeeName} has no active contract",
                    'details' => "Participation ID: {$participation->id}, Status: {$participation->status}",
                    'fixable' => true,
                    'fixed' => false,
                    'fix_fn' => function () use ($participation) {
                        $participation->update(['status' => 'TERMINATED']);
                    }
                ];

                $this->issues[] = $issue;
                $this->fixable[] = $issue;

                if ($this->option('fix')) {
                    $issue['fix_fn']();
                    $issue['fixed'] = true;
                    $this->fixed++;
                    $this->line("   → Fixed: Set participation #{$participation->id} to TERMINATED");
                }
            }
            $this->warn("   Found {$orphaned->count()} orphaned participations");
        } else {
            $this->line('   ✓ No orphaned participations');
        }
    }

    /**
     * Check for participations without components
     */
    protected function checkMissingComponents()
    {
        $this->info('2. Checking for participations without components...');

        $missing = InsuranceParticipation::where('status', 'ACTIVE')
            ->whereDoesntHave('components')
            ->with('employee')
            ->get();

        if ($missing->count() > 0) {
            foreach ($missing as $participation) {
                $employeeName = $participation->employee
                    ? "{$participation->employee->full_name} (ID: {$participation->employee_id})"
                    : "Employee ID: {$participation->employee_id}";

                $issue = [
                    'type' => 'Missing Components',
                    'message' => "Participation #{$participation->id} for {$employeeName} has no components",
                    'details' => "Created: {$participation->created_at}",
                    'fixable' => false,
                    'fixed' => false,
                ];

                $this->issues[] = $issue;
            }
            $this->warn("   Found {$missing->count()} participations without components");
            $this->comment('   → Manual action required: Add components or terminate participation');
        } else {
            $this->line('   ✓ All participations have components');
        }
    }

    /**
     * Check for invalid rate_total values
     */
    protected function checkInvalidRateTotals()
    {
        $this->info('3. Checking for invalid rate_total values...');

        $invalid = InsuranceParticipationComponent::whereNotNull('rate_total')
            ->get()
            ->filter(function ($pc) {
                if (!$pc->component) return false;

                $expectedTotal = $pc->component->default_rate_employee + $pc->component->default_rate_employer;
                $actualTotal = $pc->rate_total;

                // Allow small floating point differences (0.0001)
                return abs($actualTotal - $expectedTotal) > 0.0001;
            });

        if ($invalid->count() > 0) {
            foreach ($invalid as $pc) {
                $expectedTotal = $pc->component->default_rate_employee + $pc->component->default_rate_employer;

                $issue = [
                    'type' => 'Invalid Rate Total',
                    'message' => "Component #{$pc->id} has incorrect rate_total: {$pc->rate_total} (expected: {$expectedTotal})",
                    'details' => "Participation: {$pc->insurance_participation_id}, Component: {$pc->component->code}",
                    'fixable' => true,
                    'fixed' => false,
                    'fix_fn' => function () use ($pc, $expectedTotal) {
                        $pc->update(['rate_total' => $expectedTotal]);
                    }
                ];

                $this->issues[] = $issue;
                $this->fixable[] = $issue;

                if ($this->option('fix')) {
                    $issue['fix_fn']();
                    $issue['fixed'] = true;
                    $this->fixed++;
                    $this->line("   → Fixed: Updated rate_total for component #{$pc->id}");
                }
            }
            $this->warn("   Found {$invalid->count()} invalid rate_total values");
        } else {
            $this->line('   ✓ All rate_total values are correct');
        }
    }

    /**
     * Check for inactive components still referenced
     */
    protected function checkInactiveComponentReferences()
    {
        $this->info('4. Checking for inactive component references...');

        $inactiveRefs = InsuranceParticipationComponent::where('is_enabled', true)
            ->whereHas('component', function ($query) {
                $query->where('is_active', false);
            })
            ->with(['component', 'participation.employee'])
            ->get();

        if ($inactiveRefs->count() > 0) {
            foreach ($inactiveRefs as $pc) {
                $employeeName = $pc->participation->employee
                    ? $pc->participation->employee->full_name
                    : "Employee ID: {$pc->participation->employee_id}";

                $issue = [
                    'type' => 'Inactive Component Reference',
                    'message' => "Participation #{$pc->insurance_participation_id} ({$employeeName}) references inactive component {$pc->component->code}",
                    'details' => "Component was deactivated but still enabled in participation",
                    'fixable' => false, // Requires manual review
                    'fixed' => false,
                ];

                $this->issues[] = $issue;
            }
            $this->warn("   Found {$inactiveRefs->count()} references to inactive components");
            $this->comment('   → Manual action required: Review and decide whether to disable or re-activate');
        } else {
            $this->line('   ✓ No inactive component references');
        }
    }

    /**
     * Check for duplicate active participations for same employee
     */
    protected function checkDuplicateActiveParticipations()
    {
        $this->info('5. Checking for duplicate active participations...');

        $duplicates = InsuranceParticipation::select('employee_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'ACTIVE')
            ->groupBy('employee_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            foreach ($duplicates as $dup) {
                $employee = Employee::find($dup->employee_id);
                $employeeName = $employee ? $employee->full_name : "ID: {$dup->employee_id}";

                $participations = InsuranceParticipation::where('employee_id', $dup->employee_id)
                    ->where('status', 'ACTIVE')
                    ->get();

                $issue = [
                    'type' => 'Duplicate Participations',
                    'message' => "Employee {$employeeName} has {$dup->count} active participations",
                    'details' => "IDs: " . $participations->pluck('id')->implode(', '),
                    'fixable' => false,
                    'fixed' => false,
                ];

                $this->issues[] = $issue;
            }
            $this->warn("   Found {$duplicates->count()} employees with duplicate participations");
            $this->comment('   → Manual action required: Keep most recent, terminate others');
        } else {
            $this->line('   ✓ No duplicate active participations');
        }
    }

    /**
     * Check for participations with missing insurance_salary
     */
    protected function checkMissingInsuranceSalary()
    {
        $this->info('6. Checking for missing insurance_salary...');

        $missing = InsuranceParticipation::where('status', 'ACTIVE')
            ->where(function ($query) {
                $query->whereNull('insurance_salary')
                    ->orWhere('insurance_salary', '<=', 0);
            })
            ->with('employee')
            ->get();

        if ($missing->count() > 0) {
            foreach ($missing as $participation) {
                $employeeName = $participation->employee
                    ? "{$participation->employee->full_name} (ID: {$participation->employee_id})"
                    : "Employee ID: {$participation->employee_id}";

                $issue = [
                    'type' => 'Missing Insurance Salary',
                    'message' => "Participation #{$participation->id} for {$employeeName} has no insurance_salary",
                    'details' => "Current value: " . ($participation->insurance_salary ?? 'NULL'),
                    'fixable' => false,
                    'fixed' => false,
                ];

                $this->issues[] = $issue;
            }
            $this->warn("   Found {$missing->count()} participations with missing insurance_salary");
            $this->comment('   → Manual action required: Set insurance_salary from contract');
        } else {
            $this->line('   ✓ All participations have insurance_salary');
        }
    }
}
