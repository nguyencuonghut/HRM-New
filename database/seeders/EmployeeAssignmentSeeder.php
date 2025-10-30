<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Gán Trưởng/Phó/Thành viên & đánh dấu assignment chính (is_primary)
 */
class EmployeeAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $emp = DB::table('employees')->pluck('id','employee_code'); // map code -> id
        $ksnb = DB::table('departments')->where('name','Phòng Kiểm Soát Nội Bộ')->first();
        $ks = DB::table('departments')->where('name','Bộ phận Kiểm Soát')->first();
        $it = DB::table('departments')->where('name','Bộ phận IT')->first();
        $hc = DB::table('departments')->where('name','Phòng Hành Chính')->first();
        $ns = DB::table('departments')->where('name','Phòng Nhân Sự')->first();
        $cl = DB::table('departments')->where('name','Phòng Chất Lượng')->first();
        $clnl = DB::table('departments')->where('name','Nhóm KCS Nguyên Liệu')->first();
        $pt = DB::table('departments')->where('name','Bộ Phận Phân Tích')->first();

        $posTruongKSNB = DB::table('positions')->where('title','Trưởng phòng Kiểm Soát Nội Bộ')->first();
        $posChuyenVienKS = DB::table('positions')->where('title','Chuyên viên Kiểm Soát')->first();
        $posNhanVienKS = DB::table('positions')->where('title','Nhân viên Kiểm Soát')->first();
        $posChuyenVienIT = DB::table('positions')->where('title','Chuyên viên IT')->first();
        $posNhanVienIT = DB::table('positions')->where('title','Nhân viên IT')->first();

        $posGdHC = DB::table('positions')->where('title','Giám đốc Hành Chính')->first();
        $nvHC = DB::table('positions')->where('title','Nhân viên Hành Chính')->first();
        $nvLX = DB::table('positions')->where('title','Nhân viên Lái Xe')->first();

        $posTruongNS = DB::table('positions')->where('title','Trưởng phòng Nhân Sự')->first();
        $posTruongNhomNS = DB::table('positions')->where('title','Trưởng nhóm Nhân Sự Kinh Doanh')->first();
        $nvNS = DB::table('positions')->where('title','Nhân viên Nhân Sự')->first();

        $posGdCL = DB::table('positions')->where('title','Giám đốc Khối Quản Lý Chất Lượng')->first();
        $posTruongKSCL = DB::table('positions')->where('title','Trưởng nhóm KCS Nguyên Liệu')->first();
        $posNvCL = DB::table('positions')->where('title','Nhân viên Chất Lượng')->first();
        $posTruongPT = DB::table('positions')->where('title','Trưởng bộ phận Phân Tích')->first();
        $posNvPT = DB::table('positions')->where('title','Nhân viên Phân Tích')->first();

        $rows = [
            // KSNB
            ['employee_id'=>$emp['312'] ?? null,'department_id'=>$ksnb->id,'position_id'=>$posTruongKSNB->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['2142'] ?? null,'department_id'=>$ks->id,'position_id'=>$posChuyenVienKS->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['2272'] ?? null,'department_id'=>$ks->id,'position_id'=>$posNhanVienKS->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['912'] ?? null,'department_id'=>$it->id,'position_id'=>$posChuyenVienIT->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['1992'] ?? null,'department_id'=>$it->id,'position_id'=>$posNhanVienIT->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],

            // HC
            ['employee_id'=>$emp['254'] ?? null,'department_id'=>$hc->id,'position_id'=>$posGdHC->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['185'] ?? null,'department_id'=>$hc->id,'position_id'=>$nvHC->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['2571'] ?? null,'department_id'=>$hc->id,'position_id'=>$nvLX->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],

            // NS
            ['employee_id'=>$emp['312'] ?? null,'department_id'=>$ns->id,'position_id'=>$posTruongNS->id ?? null,'is_primary'=>false,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['481'] ?? null,'department_id'=>$ns->id,'position_id'=>$posTruongNhomNS->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['2411'] ?? null,'department_id'=>$ns->id,'position_id'=>$nvNS->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],

            // Chất Lượng
            ['employee_id'=>$emp['468'] ?? null,'department_id'=>$cl->id,'position_id'=>$posGdCL->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['353'] ?? null,'department_id'=>$clnl->id,'position_id'=>$posTruongKSCL->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['226'] ?? null,'department_id'=>$clnl->id,'position_id'=>$posNvCL->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['233'] ?? null,'department_id'=>$clnl->id,'position_id'=>$posNvCL->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['249'] ?? null,'department_id'=>$clnl->id,'position_id'=>$posNvCL->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['248'] ?? null,'department_id'=>$pt->id,'position_id'=>$posTruongPT->id ?? null,'is_primary'=>true,'role_type'=>'HEAD'],
            ['employee_id'=>$emp['252'] ?? null,'department_id'=>$pt->id,'position_id'=>$posNvPT->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['231'] ?? null,'department_id'=>$pt->id,'position_id'=>$posNvPT->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['874'] ?? null,'department_id'=>$pt->id,'position_id'=>$posNvPT->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
            ['employee_id'=>$emp['554'] ?? null,'department_id'=>$pt->id,'position_id'=>$posNvPT->id ?? null,'is_primary'=>true,'role_type'=>'MEMBER'],
        ];

        foreach ($rows as $r) {
            if (!$r['employee_id']) continue; // bảo vệ
            DB::table('employee_assignments')->insert([
                'id' => (string) Str::uuid(),
                'employee_id' => $r['employee_id'],
                'department_id' => $r['department_id'],
                'position_id' => $r['position_id'],
                'is_primary' => $r['is_primary'],
                'role_type' => $r['role_type'],
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
