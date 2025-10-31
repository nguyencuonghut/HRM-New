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
            'Bình Lục', 'Bình Mỹ', 'Bình An', 'Bình Giang', 'Bình Sơn', 'Liêm Hà',
            'Tân Thanh', 'Thanh Bình', 'Thanh Lâm', 'Thanh Liêm', 'Lý Nhân', 'Nam Xang',
            'Bắc Lý', 'Vĩnh Trụ', 'Trần Thương', 'Nhân Hà', 'Nam Lý', 'Nam Trực',
            'Nam Minh', 'Nam Đồng', 'Nam Ninh', 'Nam Hồng', 'Minh Tân', 'Hiển Khánh',
            'Vụ Bản', 'Liên Minh', 'Ý Yên', 'Yên Đồng', 'Yên Cường', 'Vạn Thắng',
            'Vũ Dương', 'Tân Minh', 'Phong Doanh', 'Cổ Lễ', 'Ninh Giang', 'Cát Thành',
            'Trực Ninh', 'Quang Hưng', 'Minh Thái', 'Ninh Cường', 'Xuân Trường',
            'Xuân Hưng', 'Xuân Giang', 'Xuân Hồng', 'Hải Hậu', 'Hải Anh', 'Hải Tiến',
            'Hải Hưng', 'Hải An', 'Hải Quang', 'Hải Xuân', 'Hải Thịnh', 'Giao Minh',
            'Giao Hòa', 'Giao Thủy', 'Giao Phúc', 'Giao Hưng', 'Giao Bình', 'Giao Ninh',
            'Đồng Thịnh', 'Nghĩa Hưng', 'Nghĩa Sơn', 'Hồng Phong', 'Quỹ Nhất', 'Nghĩa Lâm',
            'Rạng Đông', 'Gia Viễn', 'Đại Hoàng', 'Gia Hưng', 'Gia Phong', 'Gia Vân',
            'Gia Trấn', 'Nho Quan', 'Gia Lâm', 'Gia Tường', 'Phú Sơn', 'Cúc Phương',
            'Phú Long', 'Thanh Sơn', 'Quỳnh Lưu', 'Yên Khánh', 'Khánh Nhạc', 'Khánh Thiện',
            'Khánh Hội', 'Khánh Trung', 'Yên Mô', 'Yên Từ', 'Yên Mạc', 'Đồng Thái',
            'Chất Bình', 'Kim Sơn', 'Quang Thiện', 'Phát Diệm', 'Lai Thành', 'Định Hóa',
            'Bình Minh', 'Kim Đông', 'Duy Tiên', 'Duy Tân', 'Đồng Văn', 'Duy Hà',
            'Tiên Sơn', 'Lê Hồ', 'Nguyễn Úy', 'Lý Thường Kiệt', 'Kim Thanh', 'Tam Chúc',
            'Kim Bảng', 'Hà Nam', 'Phù Vân', 'Châu Sơn', 'Phủ Lý', 'Liêm Tuyền', 'Nam Định',
            'Thiên Trường', 'Đông A', 'Vị Khê', 'Thành Nam', 'Trường Thi', 'Hồng Quang',
            'Mỹ Lộc', 'Tây Hoa Lư', 'Hoa Lư', 'Nam Hoa Lư', 'Đông Hoa Lư', 'Tam Điệp',
            'Yên Sơn', 'Trung Sơn', 'Yên Thắng',
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
