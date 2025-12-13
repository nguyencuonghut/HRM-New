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

                // Lấy contract cũ nhất để xác định start_date
                $oldestContract = Contract::where('employee_id', $employee->id)
                    ->oldest('start_date')
                    ->first();

                $startDate = $oldestContract?->start_date
                    ?? $employee->hire_date
                    ?? $employee->created_at->toDateString();

                // Cập nhật hire_date nếu khác
                if ($employee->hire_date != $startDate) {
                    $employee->update(['hire_date' => $startDate]);
                    $this->command->info("  📅 Updated hire_date for {$employee->employee_code} to {$startDate}");
                }

                // Lấy contract MỚI NHẤT để xác định end_date và is_current
                $latestContract = Contract::where('employee_id', $employee->id)
                    ->latest('end_date')
                    ->first();

                // Xác định is_current, end_date dựa vào contract mới nhất
                $isCurrent = true;
                $endDate = null;
                $endReason = null;

                if ($latestContract && $latestContract->end_date) {
                    // Nếu contract đã hết hạn (end_date < today)
                    if ($latestContract->end_date->isPast()) {
                        $isCurrent = false;
                        $endDate = $latestContract->end_date->toDateString();
                        $endReason = 'CONTRACT_END';

                        // Cập nhật employee status nếu cần
                        if ($employee->status === 'ACTIVE') {
                            $employee->update(['status' => 'TERMINATED']);
                            $this->command->info("  📍 Updated status to TERMINATED for {$employee->employee_code} (contract expired {$endDate})");
                        }
                    }
                } elseif (!in_array($employee->status, ['ACTIVE', 'ON_LEAVE'])) {
                    // Không có contract hoặc contract không có end_date, dựa vào status
                    $isCurrent = false;
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
