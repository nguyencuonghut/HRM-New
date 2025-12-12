<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\Contract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateExistingEmployeesToEmploymentSeeder extends Seeder
{
    /**
     * Chạy seeder này để tạo EmployeeEmployment records từ dữ liệu hiện tại
     *
     * Logic:
     * - Mỗi employee có hire_date → tạo 1 employment với start_date = hire_date
     * - is_current = (status === 'ACTIVE')
     * - Nếu status !== 'ACTIVE' → có thể set end_date = updated_at (hoặc null)
     */
    public function run(): void
    {
        $this->command->info('🔄 Migrating existing employees to employment periods...');

        DB::beginTransaction();
        try {
            $employees = Employee::all();
            $created = 0;
            $skipped = 0;

            foreach ($employees as $employee) {
                // Check if already has employment
                if ($employee->employments()->exists()) {
                    $this->command->warn("  ⚠ Employee {$employee->employee_code} already has employments, skipping...");
                    $skipped++;
                    continue;
                }

                // Determine start_date
                $startDate = $employee->hire_date ?? $employee->created_at->toDateString();

                // Determine if current
                $isCurrent = in_array($employee->status, ['ACTIVE', 'ON_LEAVE']);

                // Determine end_date and reason
                $endDate = null;
                $endReason = null;

                if (!$isCurrent) {
                    // Employee is terminated
                    $endDate = $employee->updated_at->toDateString();
                    $endReason = match ($employee->status) {
                        'TERMINATED' => 'TERMINATION',
                        'INACTIVE' => 'RESIGN',
                        default => 'OTHER',
                    };
                }

                // Create employment record
                $employment = EmployeeEmployment::create([
                    'employee_id' => $employee->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'end_reason' => $endReason,
                    'is_current' => $isCurrent,
                    'note' => 'Migrated from existing employee data',
                ]);

                // Link existing contracts to this employment
                Contract::where('employee_id', $employee->id)
                    ->whereNull('employment_id')
                    ->update(['employment_id' => $employment->id]);

                $this->command->info("  ✓ Created employment for {$employee->employee_code} (start: {$startDate}, current: " . ($isCurrent ? 'Yes' : 'No') . ")");
                $created++;
            }

            DB::commit();

            $this->command->info("✅ Migration completed!");
            $this->command->info("   Created: {$created}");
            $this->command->info("   Skipped: {$skipped}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Migration failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
