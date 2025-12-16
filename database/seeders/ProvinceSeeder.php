<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Danh sách 34 tỉnh/thành (cấp tỉnh) có hiệu lực từ 12/06/2025
 * Nguồn: Chinhphu.vn / Báo Tin tức (TTXVN) / Nghị quyết 202/2025/QH15
 * https://baotintuc.vn/... (Infographics liệt kê 34 đơn vị)
 * https://baochinhphu.vn/... (tin QH thông qua)
 */
class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Danh sách 34 đơn vị cấp tỉnh/thành (có tiền tố "Tỉnh"/"Thành phố" để đồng bộ với dữ liệu JSON wards_all_qd19_2025.json)
        // Danh sách mã tỉnh/thành theo file đính kèm, giữ nguyên tên cũ để mapping với WardSeeder
        $codes = [
            '01','04','08','11','12','14','15','19','20','22','24','25','31','33','37','38','40','42','44','46','48','51','52','56','66','68','75','79','80','82','86','91','92','96'
        ];
        $names = [
            'Tỉnh An Giang',
            'Tỉnh Bắc Ninh',
            'Tỉnh Cà Mau',
            'Tỉnh Cao Bằng',
            'Thành phố Cần Thơ',
            'Thành phố Đà Nẵng',
            'Tỉnh Đắk Lắk',
            'Tỉnh Điện Biên',
            'Tỉnh Đồng Nai',
            'Tỉnh Đồng Tháp',
            'Tỉnh Gia Lai',
            'Thành phố Hà Nội',
            'Tỉnh Hà Tĩnh',
            'Thành phố Hải Phòng',
            'Thành phố Huế',
            'Tỉnh Hưng Yên',
            'Tỉnh Khánh Hòa',
            'Tỉnh Lai Châu',
            'Tỉnh Lạng Sơn',
            'Tỉnh Lào Cai',
            'Tỉnh Lâm Đồng',
            'Tỉnh Nghệ An',
            'Tỉnh Ninh Bình',
            'Tỉnh Phú Thọ',
            'Tỉnh Quảng Ngãi',
            'Tỉnh Quảng Ninh',
            'Tỉnh Quảng Trị',
            'Tỉnh Sơn La',
            'Tỉnh Tây Ninh',
            'Tỉnh Thái Nguyên',
            'Tỉnh Thanh Hóa',
            'Thành phố Hồ Chí Minh',
            'Tỉnh Tuyên Quang',
            'Tỉnh Vĩnh Long',
        ];
        $rows = [];
        foreach ($names as $i => $n) {
            $rows[] = [
                'id' => (string) Str::uuid(), // UUID
                'code' => $codes[$i],
                'name' => $n,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('provinces')->truncate();
        DB::table('provinces')->insert($rows);
    }
}
