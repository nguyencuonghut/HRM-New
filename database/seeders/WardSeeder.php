<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Danh sách xã/phường của tỉnh Ninh Bình (mới) sau sắp xếp 2025: 129 đơn vị (97 xã, 32 phường).
 * Nguồn chính thống:
 *  - Cổng TTĐT Chính phủ: https://xaydungchinhsach.chinhphu.vn/... (Chi tiết 129 xã, phường Ninh Bình)
 *  - Cổng thông tin Ninh Binh: https://ninhbinh.gov.vn/... (129 đơn vị hành chính cấp xã)
 * Bạn chỉ cần điền mảng $wards theo danh mục tại link trên.
 */
class WardSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy province_id của 'Ninh Bình' đã được ProvinceSeeder tạo
        $province = DB::table('provinces')->where('name', 'Ninh Bình')->first();
        if (!$province) {
            $this->command->error('Không tìm thấy tỉnh Ninh Bình trong bảng provinces. Hãy chạy ProvinceSeeder trước.');
            return;
        }

        $now = now();

        // TODO: Điền đầy đủ 129 tên xã/phường theo danh sách tại nguồn Chính phủ (2025).
        // Để bạn test nhanh UI, mình seed một tập con mẫu (có cả "phường" và "xã"):
        $sample = [
            'Phường Bích Đào','Phường Tân Thành','Phường Nam Bình','Phường Thanh Bình','Phường Vân Giang',
            'Xã Gia Hưng','Xã Gia Vân','Xã Gia Phong','Xã Quỳnh Lưu','Xã Cúc Phương',
            'Phường Trung Sơn','Phường Tây Sơn','Phường Yên Bình','Phường Nam Sơn',
        ];

        $rows = [];
        foreach ($sample as $name) {
            $rows[] = [
                'id' => (string) Str::uuid(),         // UUID
                'province_id' => $province->id,       // FK -> provinces.id
                'code' => Str::slug($name, '_'),      // code gợi ý, bạn có thể thay bằng mã TMS/HC nếu có
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Gợi ý: dùng upsert để tránh xóa dữ liệu cũ nếu đã nạp.
        DB::table('wards')->upsert($rows, ['province_id','code'], ['name','updated_at']);
    }
}
