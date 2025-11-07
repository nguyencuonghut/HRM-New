<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $schools = [
            ['code' => 'VNU-HANOI',          'name' => 'Đại học Quốc gia Hà Nội'],
            ['code' => 'HUST',               'name' => 'Trường Đại học Bách khoa Hà Nội'],
            ['code' => 'NEU',                'name' => 'Trường Đại học Kinh tế Quốc dân'],
            ['code' => 'FTU',                'name' => 'Trường Đại học Ngoại thương (Hà Nội)'],
            ['code' => 'NUCE',               'name' => 'Trường Đại học Xây dựng Hà Nội'],
            ['code' => 'PTIT',               'name' => 'Học viện Công nghệ Bưu chính Viễn thông (Hà Nội)'],
            ['code' => 'VAA',                'name' => 'Học viện Tài chính'],
            ['code' => 'BA',                 'name' => 'Học viện Ngân hàng'],
            ['code' => 'TMU',                'name' => 'Trường Đại học Thương mại'],
            ['code' => 'UTT',                'name' => 'Trường Đại học Giao thông Vận tải (Hà Nội)'],
            ['code' => 'HUMG',               'name' => 'Trường Đại học Mỏ - Địa chất'],
            ['code' => 'VNUA',               'name' => 'Học viện Nông nghiệp Việt Nam'],
            ['code' => 'HNUE',               'name' => 'Trường Đại học Sư phạm Hà Nội'],
            ['code' => 'HMU',                'name' => 'Trường Đại học Y Hà Nội'],
            ['code' => 'HUP',                'name' => 'Trường Đại học Dược Hà Nội'],
            ['code' => 'EPU',                'name' => 'Trường Đại học Điện lực'],
            ['code' => 'TLU',                'name' => 'Trường Đại học Thủy lợi (Hà Nội)'],
            ['code' => 'TNU',                'name' => 'Đại học Thái Nguyên (đại học vùng)'],
            ['code' => 'VIMARU',             'name' => 'Trường Đại học Hàng hải Việt Nam (Hải Phòng)'],
            ['code' => 'HPU',                'name' => 'Trường Đại học Hải Phòng'],
            // Bạn có thể thay thế/điều chỉnh mã code theo chuẩn nội bộ nếu muốn.
        ];

        $rows = array_map(function ($s) use ($now) {
            return [
                'id'         => (string) Str::uuid(),
                'code'       => $s['code'],
                'name'       => $s['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $schools);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('schools')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::table('schools')->insert($rows);
    }
}
