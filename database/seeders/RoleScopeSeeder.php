<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Ví dụ: cấp quyền "manager" cho Trưởng phòng KSNB theo nhánh phòng ban
 * (Giả định bạn đã có vai trò 'manager' trong bảng roles của spatie/permission)
 */
class RoleScopeSeeder extends Seeder
{
    public function run(): void
    {
        $role = DB::table('roles')->where('name','manager')->first();
        $dept = DB::table('departments')->where('name','Phòng Kiểm Soát Nội Bộ')->first();
        $headAssignment = DB::table('employee_assignments')
            ->where('department_id', $dept->id ?? null)
            ->where('role_type','HEAD')
            ->first();

        if ($role && $dept && $headAssignment) {
            DB::table('role_scopes')->insert([
                'id' => (string) Str::uuid(),
                'role_id' => $role->id,
                'employee_id' => $headAssignment->employee_id,
                'department_id' => $dept->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
