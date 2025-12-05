<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Cây tổ chức nhiều cấp:
 * - type enum: DEPARTMENT (Phòng/Ban), UNIT (Bộ phận), TEAM (Nhóm)
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('departments')->truncate();

        // Helper tạo node
        $make = function ($name, $type='DEPARTMENT', $parentId=null, $order=0) use ($now) {
            $id = (string) Str::uuid();
            DB::table('departments')->insert([
                'id' => $id,
                'parent_id' => $parentId,
                'name' => $name,
                'code' => $this->generateCodeFromName($name),
                'order_index' => $order,
                'is_active' => true,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return $id;
        };

        // Ban lãnh đạo (root-level)
        $banLanhDao = $make('Ban lãnh đạo', 'DEPARTMENT', null, 1);

        // Phòng Kiểm Soát Nội Bộ
        $ksnb = $make('Phòng Kiểm Soát Nội Bộ', 'DEPARTMENT', null, 2);
        $make('Bộ phận Kiểm Soát', 'UNIT', $ksnb, 1);
        $make('Bộ phận IT', 'UNIT', $ksnb, 2);

        // Phòng Hành Chính
        $hcns = $make('Phòng Hành Chính', 'DEPARTMENT', null, 3);
        $make('Tổ nhà bếp', 'TEAM', $hcns, 1);
        $make('Tổ tạp vụ', 'TEAM', $hcns, 2);
        $make('Ban Bảo Vệ', 'TEAM', $hcns, 3);
        $make('Tổ Lái Xe', 'TEAM', $hcns, 4);

        // Phòng Kinh Doanh
        $kd = $make('Phòng Kinh Doanh', 'DEPARTMENT', null, 4);
        $make('Bộ phận Kinh Doanh Gia Súc Gia Cầm', 'UNIT', $kd, 1);
        $make('Bộ phận Kinh Doanh Thủy Sản', 'UNIT', $kd, 2);
        $make('Bộ phận Kỹ Thuật Thị Trường', 'UNIT', $kd, 3);
        $make('Bộ phận Kỹ Thuật Trại', 'UNIT', $kd, 4);
        $make('Bộ phận Sale Admin', 'UNIT', $kd, 5);
        $make('Bộ phận Kinh Doanh Thuốc', 'UNIT', $kd, 6);

        // Phòng Kế Toán
        $kt = $make('Phòng Kế Toán', 'DEPARTMENT', null, 5);
        $make('Bộ phận Kế Toán Nhà Máy', 'UNIT', $kt, 1);
        $make('Bộ phận Kế Toán Kho', 'UNIT', $kt, 2);
        $make('Tổ Bán Hàng', 'TEAM', $kt, 3);
        $make('Bộ phận Cân', 'UNIT', $kt, 4);

        // Phòng Thu Mua
        $tm = $make('Phòng Thu Mua', 'DEPARTMENT', null, 6);
        $make('Bộ phận Admin Thu Mua', 'UNIT', $tm, 1);
        $make('Bộ phận Xuất Nhập Khẩu', 'UNIT', $tm, 2);
        $make('Bộ phận Mua Hàng', 'UNIT', $tm, 3);

        // Phòng Bảo Trì
        $bt = $make('Phòng Bảo Trì', 'DEPARTMENT', null, 7);
        $make('Bộ phận Bảo Trì Cơ', 'UNIT', $bt, 1);
        $make('Bộ phận Bảo Trì Điện', 'UNIT', $bt, 2);

        // Phòng Sản Xuất
        $sx = $make('Phòng Sản Xuất', 'DEPARTMENT', null, 8);
        $make('Bộ phận Sản Xuất Gia Súc Gia Cầm', 'UNIT', $sx, 1);
        $make('Bộ phận Sản Xuất Thủy Sản', 'UNIT', $sx, 2);

        // Bộ phận Kho (root-level Unit)
        $kho = $make('Bộ phận Kho', 'UNIT', null, 9);
        $make('Tổ Kho Nguyên Liệu', 'TEAM', $kho, 1);
        $make('Tổ Kho Thành Phẩm', 'TEAM', $kho, 2);

        // Bộ phận Pháp Chế (root-level Unit)
        $make('Phòng Pháp Chế', 'DEPARTMENT', null, 10);

        // Phòng Chất Lượng
        $ktdd = $make('Phòng Chất Lượng', 'DEPARTMENT', null, 11);
        $make('Bộ Phận Phân Tích', 'UNIT', $ktdd, 1);
        $make('Tổ Trộn Mix', 'TEAM', $ktdd, 2);
        $make('Nhóm KCS Nguyên Liệu', 'TEAM', $ktdd, 3);
        $make('Nhóm KCS Thành Phẩm Gia Súc', 'TEAM', $ktdd, 4);
        $make('Nhóm KCS Thành Phẩm Thủy Sản', 'TEAM', $ktdd, 5);

        // Ban Dự Án
        $make('Ban Dự Án', 'DEPARTMENT', null, 12);

        // Phòng Trại (chưa có chi tiết tổ/đội)
        $make('Phòng Trại', 'DEPARTMENT', null, 13);

        // Phòng Kỹ Thuật Dinh Dưỡng (chưa có chi tiết)
        $make('Phòng Kỹ Thuật Dinh Dưỡng', 'DEPARTMENT', null, 14);

        // Phòng Nhân Sự
        $hcns = $make('Phòng Nhân Sự', 'DEPARTMENT', null, 15);
        $nskd = $make('Nhóm Nhân Sự Kinh Doanh', 'TEAM', $hcns, 1);
        $nsnm = $make('Nhóm Nhân Sự Nhà Máy', 'TEAM', $hcns, 2);
    }

    /**
     * Tạo mã viết tắt (Acronym) từ một chuỗi tên.
     * Sử dụng Str::ascii để loại bỏ dấu và regex để lấy ký tự đầu tiên của mỗi từ.
     * Ví dụ: "Phòng Hành Chính" -> "PHC"
     *
     * @param string $name Tên đầy đủ của phòng/ban.
     * @return string Mã viết tắt.
     */
    private function generateCodeFromName(string $name): string
    {
        // 1. Loại bỏ dấu tiếng Việt và chuẩn hóa về ký tự ASCII
        // Ví dụ: "Phòng Hành Chính" -> "Phong Hanh Chinh"
        $asciiName = Str::ascii($name);

        // 2. Loại bỏ các ký tự đặc biệt, dấu câu, và chuẩn hóa khoảng trắng
        // Thay thế các ký tự không phải chữ cái, số, hoặc khoảng trắng bằng khoảng trắng
        $cleanedName = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $asciiName);

        // 3. Sử dụng regex để tìm ký tự đầu tiên của mỗi từ và nối lại
        // Regex: \b(\w) tìm ký tự chữ cái/số đầu tiên sau giới hạn từ.
        preg_match_all('/\b(\w)/', $cleanedName, $matches);

        // 4. Nối các ký tự lại và chuyển thành chữ hoa
        if (isset($matches[1])) {
            return strtoupper(implode('', $matches[1]));
        }

        return '';
    }
}
