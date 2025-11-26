<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeAssignment;
use App\Models\RoleScope;
use Spatie\Permission\Models\Role;

/**
 * Seeder để setup Director cho phòng Nhân Sự
 * (Người này sẽ phê duyệt TẤT CẢ hợp đồng trong công ty)
 */
class ContractApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Setting up Contract Approval workflow...');

        // 1. Tìm hoặc tạo role Director
        $directorRole = Role::firstOrCreate(['name' => 'Director'], [
            'guard_name' => 'web'
        ]);
        $this->command->info("✓ Director role: {$directorRole->name}");

        // 2. Tìm phòng Nhân Sự
        $hrDepartment = Department::whereIn('name', [
            'Phòng Nhân Sự',
        ])->first();

        if (!$hrDepartment) {
            $this->command->warn('⚠ Không tìm thấy phòng Nhân Sự, tạo mới...');
            $hrDepartment = Department::create([
                'id' => Str::uuid(),
                'type' => 'UNIT',
                'name' => 'Phòng Nhân Sự',
                'code' => 'HR',
                'is_active' => true,
                'order_index' => 1,
            ]);
        }
        $this->command->info("✓ HR Department: {$hrDepartment->name}");

        // 3. Tìm Director user từ UserSeeder
        $directorEmail = 'gd@honghafeed.com.vn';
        $directorUser = User::where('email', $directorEmail)->first();

        if (!$directorUser) {
            $this->command->error("✗ Không tìm thấy user {$directorEmail}. Chạy UserSeeder trước!");
            return;
        }

        // Assign Director role nếu chưa có
        if (!$directorUser->hasRole('Director')) {
            $directorUser->assignRole('Director');
            $this->command->info("✓ Assigned Director role to: {$directorUser->email}");
        }

        // 4. Lấy Employee record từ EmployeeSeeder
        $directorEmployee = Employee::where('user_id', $directorUser->id)->first();

        if (!$directorEmployee) {
            $this->command->error('✗ Không tìm thấy Employee record cho Director. Chạy EmployeeSeeder trước!');
            return;
        }
        $this->command->info("✓ Director Employee: {$directorEmployee->full_name} ({$directorEmployee->employee_code})");

        // 5. Tạo EmployeeAssignment (HEAD của phòng Nhân Sự)
        $headAssignment = EmployeeAssignment::where('department_id', $hrDepartment->id)
            ->where('role_type', 'HEAD')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$headAssignment) {
            $this->command->warn('⚠ Tạo HEAD assignment cho phòng Nhân Sự...');

            // Tìm position "Trưởng phòng" hoặc tạo mới
            $headPosition = DB::table('positions')
                ->where('title', 'Trưởng phòng')
                ->where('department_id', $hrDepartment->id)
                ->first();

            if (!$headPosition) {
                $headPositionId = Str::uuid();
                DB::table('positions')->insert([
                    'id' => $headPositionId,
                    'department_id' => $hrDepartment->id,
                    'title' => 'Trưởng phòng',
                    'level' => 'MIDDLE',
                    'order_index' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $headPositionId = $headPosition->id;
            }

            $headAssignment = EmployeeAssignment::create([
                'id' => Str::uuid(),
                'employee_id' => $directorEmployee->id,
                'department_id' => $hrDepartment->id,
                'position_id' => $headPositionId,
                'role_type' => 'HEAD',
                'is_primary' => true,
                'status' => 'ACTIVE',
                'start_date' => now()->subYears(5),
            ]);

            // Update department.head_assignment_id
            $hrDepartment->update([
                'head_assignment_id' => $headAssignment->id
            ]);
        }
        $this->command->info("✓ HEAD Assignment created/found");

        // 6. Tạo RoleScope (Director cho phòng Nhân Sự)
        $existingRoleScope = RoleScope::where('role_id', $directorRole->id)
            ->where('department_id', $hrDepartment->id)
            ->first();

        if (!$existingRoleScope) {
            RoleScope::create([
                'id' => Str::uuid(),
                'role_id' => $directorRole->id,
                'employee_id' => $directorEmployee->id,
                'department_id' => $hrDepartment->id,
            ]);
            $this->command->info("✓ RoleScope created: Director for {$hrDepartment->name}");
        } else {
            // Update existing
            $existingRoleScope->update([
                'employee_id' => $directorEmployee->id,
            ]);
            $this->command->info("✓ RoleScope updated");
        }

        // 7. Assign permissions cho Director
        $contractPermissions = [
            'view contracts',
            'create contracts',
            'approve contracts',
            'recall contracts',
        ];

        foreach ($contractPermissions as $permName) {
            $permission = DB::table('permissions')->where('name', $permName)->first();
            if ($permission) {
                // Assign to role
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $directorRole->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $directorRole->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
        $this->command->info("✓ Permissions assigned to Director role");

        // Summary
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════');
        $this->command->info('✅ Contract Approval Setup Complete!');
        $this->command->info('═══════════════════════════════════════════════');
        $this->command->info("Department: {$hrDepartment->name}");
        $this->command->info("Director: {$directorUser->name} ({$directorUser->email})");
        $this->command->info("Employee: {$directorEmployee->full_name} (Code: {$directorEmployee->employee_code})");
        $this->command->info("Password: Hongha@123");
        $this->command->info('');
        $this->command->info('Workflow: HR Staff → Submit → HR Head (Director) → Active');
        $this->command->info('═══════════════════════════════════════════════');
    }
}
