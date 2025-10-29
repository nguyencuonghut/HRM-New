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

        // Danh sách 34 đơn vị cấp tỉnh/thành sau sắp xếp 2025 (chữ in hoa để tránh sai dấu khi so khớp)
        // Tham chiếu danh sách hiển thị trên infographics Báo Tin tức TTXVN (02/07/2025)
        // (AN GIANG, BẮC NINH, CÀ MAU, CAO BẰNG, CẦN THƠ, ĐÀ NẴNG, ĐẮK LẮK, ĐIỆN BIÊN,
        //  ĐỒNG NAI, ĐỒNG THÁP, GIA LAI, HÀ NỘI, HÀ TĨNH, HẢI PHÒNG, HUẾ, HƯNG YÊN,
        //  KHÁNH HÒA, LAI CHÂU, LẠNG SƠN, LÀO CAI, LÂM ĐỒNG, NGHỆ AN, NINH BÌNH, PHÚ THỌ,
        //  QUẢNG NGÃI, QUẢNG NINH, QUẢNG TRỊ, SƠN LA, TÂY NINH, THÁI NGUYÊN, THANH HÓA,
        //  TP HỒ CHÍ MINH, TUYÊN QUANG, VĨNH LONG)
        $names = [
            'An Giang','Bắc Ninh','Cà Mau','Cao Bằng','Cần Thơ','Đà Nẵng','Đắk Lắk','Điện Biên',
            'Đồng Nai','Đồng Tháp','Gia Lai','Hà Nội','Hà Tĩnh','Hải Phòng','Huế','Hưng Yên',
            'Khánh Hòa','Lai Châu','Lạng Sơn','Lào Cai','Lâm Đồng','Nghệ An','Ninh Bình','Phú Thọ',
            'Quảng Ngãi','Quảng Ninh','Quảng Trị','Sơn La','Tây Ninh','Thái Nguyên','Thanh Hóa',
            'TP Hồ Chí Minh','Tuyên Quang','Vĩnh Long',
        ];

        $rows = [];
        foreach ($names as $n) {
            $rows[] = [
                'id' => (string) Str::uuid(), // UUID
                'code' => Str::slug($n, '_'), // code gợi ý (có thể thay bằng mã TMS/ISO nếu bạn có)
                'name' => $n,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('provinces')->truncate();
        DB::table('provinces')->insert($rows);
    }
}
