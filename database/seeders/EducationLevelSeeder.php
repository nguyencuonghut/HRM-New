<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Thứ tự gợi ý từ thấp -> cao để dễ sắp xếp/filter
        $levels = [
            ['code' => 'PRIMARY',        'name' => 'Tiểu học',                 'order_index' => 0],
            ['code' => 'LOWER_SECOND',   'name' => 'Trung học cơ sở',          'order_index' => 1],
            ['code' => 'UPPER_SECOND',   'name' => 'Trung học phổ thông',      'order_index' => 2],
            ['code' => 'INTERMEDIATE',   'name' => 'Trung cấp',                 'order_index' => 3],
            ['code' => 'INTERMEDIATE_V', 'name' => 'Trung cấp nghề',            'order_index' => 4],
            ['code' => 'COLLEGE',        'name' => 'Cao đẳng',                  'order_index' => 5],
            ['code' => 'COLLEGE_V',      'name' => 'Cao đẳng nghề',             'order_index' => 6],
            ['code' => 'BACHELOR',       'name' => 'Đại học',                   'order_index' => 7],
            ['code' => 'MASTER',         'name' => 'Thạc sĩ',                   'order_index' => 8],
            ['code' => 'DOCTOR',         'name' => 'Tiến sĩ',                   'order_index' => 9],
        ];

        $rows = array_map(function ($lv) use ($now) {
            return [
                'id'          => (string) Str::uuid(),
                'code'        => $lv['code'],
                'name'        => $lv['name'],
                'order_index' => $lv['order_index'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }, $levels);

        // Xoá & seed lại cho an toàn môi trường dev
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('education_levels')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::table('education_levels')->insert($rows);
    }
}
