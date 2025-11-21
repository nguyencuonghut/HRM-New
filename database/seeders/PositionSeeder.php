<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Vị trí cơ bản cho: Phòng Kiểm Soát Nội Bộ, Phòng Hành Chính Nhân Sự
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $deptKSNB = DB::table('departments')->where('name','Phòng Kiểm Soát Nội Bộ')->first();
        $deptIT = DB::table('departments')->where('name','Bộ phận IT')->first();
        $deptHC = DB::table('departments')->where('name','Phòng Hành Chính')->first();
        $deptNS = DB::table('departments')->where('name','Phòng Nhân Sự')->first();
        $deptCL = DB::table('departments')->where('name','Phòng Chất Lượng')->first();
        $deptPPT = DB::table('departments')->where('name','Bộ Phận Phân Tích')->first();
        if (!$deptKSNB || !$deptHC || !$deptNS || !$deptIT || !$deptCL || !$deptPPT) return;

        $rows = [
            // KSNB
            ['department_id'=>$deptKSNB->id,'title'=>'Trưởng phòng Kiểm Soát Nội Bộ','level'=>null,'insurance_base_salary'=>15000000,'position_salary'=>18000000,'competency_salary'=>18000000,'allowance' => 3000000],
            ['department_id'=>$deptKSNB->id,'title'=>'Chuyên viên Kiểm Soát','level'=>'Senior','insurance_base_salary'=>12000000,'position_salary'=>12000000,'competency_salary'=>12000000,'allowance' => 2000000],
            ['department_id'=>$deptKSNB->id,'title'=>'Nhân viên Kiểm Soát','level'=>'Junior','insurance_base_salary'=>8000000,'position_salary'=>8000000,'competency_salary'=>8000000,'allowance' => 1000000],
            ['department_id'=>$deptIT->id,'title'=>'Chuyên viên IT','level'=>'Senior','insurance_base_salary'=>18000000,'position_salary'=>18000000,'competency_salary'=>18000000,'allowance' => 4000000],
            ['department_id'=>$deptIT->id,'title'=>'Nhân viên IT ','level'=>null,'insurance_base_salary'=>10000000,'position_salary'=>9000000,'competency_salary'=>9000000,'allowance' => 1500000],

            // HC
            ['department_id'=>$deptHC->id,'title'=>'Giám đốc Hành Chính','level'=>null,'insurance_base_salary'=>35000000,'position_salary'=>38000000,'competency_salary'=>38000000,'allowance' => 3000000],
            ['department_id'=>$deptHC->id,'title'=>'Nhân viên Hành Chính','level'=>null,'insurance_base_salary'=>10000000,'position_salary'=>9000000,'competency_salary'=>9000000,'allowance' => 1500000],
            ['department_id'=>$deptHC->id,'title'=>'Nhân viên Lái Xe','level'=>null,'insurance_base_salary'=>8000000,'position_salary'=>8000000,'competency_salary'=>8000000,'allowance' => 400000],
            // NS
            ['department_id'=>$deptNS->id,'title'=>'Trưởng phòng Nhân Sự','level'=>null,'insurance_base_salary'=>35000000,'position_salary'=>38000000,'competency_salary'=>38000000,'allowance' => 3000000],
            ['department_id'=>$deptNS->id,'title'=>'Trưởng nhóm Nhân Sự Kinh Doanh','level'=>null,'insurance_base_salary'=>13000000,'position_salary'=>15000000,'competency_salary'=>15000000,'allowance' => 2500000],
            ['department_id'=>$deptNS->id,'title'=>'Nhân viên Nhân Sự','level'=>null,'insurance_base_salary'=>9000000,'position_salary'=>8000000,'competency_salary'=>8000000,'allowance' => 1500000],

            // Chất Lượng
            ['department_id'=>$deptCL->id,'title'=>'Giám đốc Khối Quản Lý Chất Lượng','level'=>null,'insurance_base_salary'=>25000000,'position_salary'=>2000000,'competency_salary'=>2000000,'allowance' => 3000000],
            ['department_id'=>$deptCL->id,'title'=>'Trưởng nhóm KCS Nguyên Liệu','level'=>null,'insurance_base_salary'=>18000000,'position_salary'=>18000000,'competency_salary'=>18000000,'allowance' => 2000000],
            ['department_id'=>$deptCL->id,'title'=>'Nhân viên Chất Lượng','level'=>null,'insurance_base_salary'=>8000000,'position_salary'=>8000000,'competency_salary'=>8000000,'allowance' => 1000000],
            ['department_id'=>$deptPPT->id,'title'=>'Trưởng bộ phận Phân Tích','level'=>null,'insurance_base_salary'=>15000000,'position_salary'=>15000000,'competency_salary'=>15000000,'allowance' => 2000000],
            ['department_id'=>$deptPPT->id,'title'=>'Nhân viên Phân Tích','level'=>null,'insurance_base_salary'=>9000000,'position_salary'=>9000000,'competency_salary'=>9000000,'allowance' => 1200000],
        ];

        foreach ($rows as &$r) {
            $r['id'] = (string) Str::uuid();
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }

        DB::table('positions')->insert($rows);
    }
}
