<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Một số nhân viên tên tiếng Việt (không dùng Faker)
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $list = [
            // KSNB
            ['employee_code'=>'312','full_name'=>'Tạ Văn Toại','phone'=>'0901000001','personal_email'=>'tavantoai@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'2142','full_name'=>'Bùi Thị Nụ','phone'=>'0901000002','personal_email'=>'buitinu@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'2272','full_name'=>'Trần Xuân Trường','phone'=>'0901000003','personal_email'=>'tranxuantruong@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'912','full_name'=>'Nguyễn Văn Cường','phone'=>'0901000003','personal_email'=>'nguyenvancuong@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'1992','full_name'=>'Phạm Hồng Hải','phone'=>'0901000003','personal_email'=>'phamhonghai@honghafeed.com.vn','status'=>'ACTIVE'],
            // HC
            ['employee_code'=>'254','full_name'=>'Hoàng Thị Ngọc Ánh','phone'=>'0901000004','personal_email'=>'hoangthingocanh@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'185','full_name'=>'Bùi Thị Nết','phone'=>'0901000006','personal_email'=>'buithinet@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'2571','full_name'=>'Bùi Thế Tuyến','phone'=>'0901000006','personal_email'=>'Tuyenbdshoaphat@gmail.com','status'=>'ACTIVE'],
            // NS
            ['employee_code'=>'481','full_name'=>'Trần Thị Bích Phương','phone'=>'0901000005','personal_email'=>'tranthibichphuong@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'2411','full_name'=>'Nguyễn Thị Ngọc Lan','phone'=>'0901000006','personal_email'=>'nguyenthingoclan@honghafeed.com.vn','status'=>'ACTIVE'],
            // Chất lượng
            ['employee_code'=>'468','full_name'=>'Phạm Thành Thứ','phone'=>'0901000007','personal_email'=>'phamthanhthu@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'353','full_name'=>'Ngô Tiến Trung','phone'=>'0901000008','personal_email'=>'ngotientrung@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'226','full_name'=>'Cao Xuân Thuyên','phone'=>'0901000009','personal_email'=>'caoxuanthuyen@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'233','full_name'=>'Phạm Thị Thúy','phone'=>'0901000010','personal_email'=>'phamthithuy@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'249','full_name'=>'Phan Thị Hằng','phone'=>'0901000011','personal_email'=>'phanthihang@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'248','full_name'=>'Trần Thị Thu Hương','phone'=>'0901000012','personal_email'=>'tranthithuhuong@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'252','full_name'=>'Nguyễn Thị Thiết','phone'=>'0901000013','personal_email'=>'nguyenthithiet@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'231','full_name'=>'Nguyễn Thị Thảo','phone'=>'0901000014','personal_email'=>'nguyenthithao@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'874','full_name'=>'Vũ Thị Nga','phone'=>'0901000015','personal_email'=>'vuthinga@honghafeed.com.vn','status'=>'ACTIVE'],
            ['employee_code'=>'554','full_name'=>'Cao Thị Phượng','phone'=>'0901000015','personal_email'=>'caothiphuong@honghafeed.com.vn','status'=>'ACTIVE'],
        ];

        foreach ($list as $r) {
            DB::table('employees')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => null, // có thể map sau
                'employee_code' => $r['employee_code'],
                'full_name' => $r['full_name'],
                'dob' => null,
                'cccd' => null,
                'cccd_issued_on' => null,
                'cccd_issued_by' => null,
                'ward_id' => null, // có thể map sau khi seed wards
                'address_street' => null,
                'phone' => $r['phone'],
                'personal_email' => $r['personal_email'],
                'hire_date' => now()->toDateString(),
                'status' => $r['status'],
                'si_number' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
