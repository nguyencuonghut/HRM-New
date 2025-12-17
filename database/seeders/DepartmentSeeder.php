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
        $make('Ban Lãnh Đạo', 'DEPARTMENT', null, 1);

        // Phòng Kiểm Soát Nội Bộ
        $ksnb = $make('Phòng Kiểm Soát Nội Bộ', 'DEPARTMENT', null, 2);
        $make('Bộ phận Kiểm Soát', 'UNIT', $ksnb, 1);
        $make('Bộ phận IT', 'UNIT', $ksnb, 2);

        // Phòng Hành Chính
        $hcns = $make('Phòng Hành Chính', 'DEPARTMENT', null, 3);
        $make('Tổ Nhà Bếp', 'TEAM', $hcns, 1);
        $make('Tổ Tạp Vụ', 'TEAM', $hcns, 2);
        $make('Ban Bảo Vệ', 'TEAM', $hcns, 3);
        $make('Tổ Lái Xe', 'TEAM', $hcns, 4);
        $make('Tổ Trồng Rau', 'TEAM', $hcns, 5);

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
        $make('Tổ Kế Toán Kho', 'TEAM', $kt, 2);
        $make('Tổ Kế Toán Bán Hàng', 'TEAM', $kt, 3);
        $make('Tổ Cân', 'TEAM', $kt, 4);

        // Phòng Thu Mua
        $tm = $make('Phòng Thu Mua', 'DEPARTMENT', null, 6);
        $make('Bộ phận Admin Thu Mua', 'UNIT', $tm, 1);

        // Phòng Bảo Trì
        $bt = $make('Phòng Bảo Trì', 'DEPARTMENT', null, 7);

        // Phòng Sản Xuất
        $sx = $make('Phòng Sản Xuất', 'DEPARTMENT', null, 8);
        $make('Bộ phận Sản Xuất Gia Súc Gia Cầm', 'UNIT', $sx, 1);
        $make('Bộ phận Sản Xuất Thủy Sản', 'UNIT', $sx, 2);
        $make('Tổ Ra Cám 1', 'TEAM', $sx, 3);
        $make('Tổ Ra Cám 2', 'TEAM', $sx, 4);
        $make('Tổ Ra Cám 3', 'TEAM', $sx, 5);
        $make('Tổ Ra Cám 8', 'TEAM', $sx, 6);
        $make('Tổ Ra Cám 9', 'TEAM', $sx, 7);
        $make('Tổ Ra Cám 10', 'TEAM', $sx, 8);
        $make('Tổ Ra Cám 11', 'TEAM', $sx, 9);

        // Bộ phận Kho (root-level Unit)
        $kho = $make('Bộ phận Kho', 'UNIT', null, 9);
        $make('Tổ Kho Nguyên Liệu', 'TEAM', $kho, 1);
        $make('Tổ Kho Thành Phẩm', 'TEAM', $kho, 2);
        $make('Tổ Kho Thuốc Thú Y', 'TEAM', $kho, 3);
        $make('Tổ Công Nhật', 'TEAM', $kho, 4);
        $make('Tổ Bốc Xếp 5', 'TEAM', $kho, 5);
        $make('Tổ Bốc Xếp 7', 'TEAM', $kho, 6);
        $make('Tổ Bốc Xếp NL1', 'TEAM', $kho, 7);

        // Bộ phận Pháp Chế (root-level Unit)
        $make('Phòng Pháp Chế', 'DEPARTMENT', null, 10);

        // Phòng Chất Lượng
        $ktdd = $make('Phòng Chất Lượng', 'DEPARTMENT', null, 11);
        $make('Bộ Phận Phòng Thí Nghiệm', 'UNIT', $ktdd, 1);
        $make('Tổ Chất Lượng Nguyên Liệu', 'TEAM', $ktdd, 2);
        $make('Tổ Chất Lượng Thành Phẩm Gia Súc', 'TEAM', $ktdd, 3);
        $make('Tổ Chất Lượng Thành Phẩm Thủy Sản', 'TEAM', $ktdd, 4);
        $make('Tổ Chất Lượng Premix + Nguyên Liệu', 'TEAM', $ktdd, 5);

        // Ban Dự Án
        $make('Ban Dự Án', 'DEPARTMENT', null, 12);

        // Phòng Trại (chưa có chi tiết tổ/đội)
        $make('Phòng Trại', 'DEPARTMENT', null, 13);

        // Phòng Kỹ Thuật Dinh Dưỡng (chưa có chi tiết)
        $ktdd = $make('Phòng Kỹ Thuật Dinh Dưỡng', 'DEPARTMENT', null, 14);
        $make('Tổ Trộn Mix', 'TEAM', $ktdd, 1);

        // Phòng Nhân Sự
        $hcns = $make('Phòng Nhân Sự', 'DEPARTMENT', null, 15);
        $make('Nhóm Nhân Sự Kinh Doanh', 'TEAM', $hcns, 1);
        $make('Nhóm Nhân Sự Nhà Máy', 'TEAM', $hcns, 2);

        // Phòng Bảo Vệ
        $make('Phòng Bảo Vệ', 'DEPARTMENT', null, 16);
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
        $asciiName = Str::ascii($name);
        $cleanedName = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $asciiName);

        // Lấy số ở cuối (nếu có)
        preg_match('/(\\d+)$/', trim($cleanedName), $numberMatch);
        $number = $numberMatch[1] ?? '';

        // Lấy ký tự đầu của mỗi từ (bỏ số ở cuối nếu có)
        $words = preg_split('/\\s+/', trim($cleanedName));
        if ($number) array_pop($words); // bỏ số cuối khỏi mảng từ

        $acronym = '';
        foreach ($words as $w) {
            if ($w !== '') $acronym .= strtoupper($w[0]);
        }
        return $acronym . $number;
    }
}
