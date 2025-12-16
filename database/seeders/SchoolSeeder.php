<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $jsonPath = database_path('data/schools_all.json');
        if (!File::exists($jsonPath)) {
            throw new \RuntimeException("Missing JSON file: {$jsonPath}. Hãy đặt file schools_all.json vào database/data/");
        }

        $schools = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        $rows = array_values(array_filter(array_map(function ($s) use ($now) {
            $name = trim($s['name'] ?? '');
            if ($name === '') return null;

            $code = trim($s['code'] ?? '');
            if ($code === '') {
                // fallback nếu record nào thiếu code
                $code = Str::upper(Str::slug($name, '-'));
            }

            return [
                'id'         => (string) Str::uuid(),
                'code'       => $code,
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $schools)));

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('schools')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::table('schools')->insert($rows);
    }
}
