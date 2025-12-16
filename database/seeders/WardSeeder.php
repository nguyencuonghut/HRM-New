<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WardSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/wards_all_qd19_2025.json');
        if (!File::exists($jsonPath)) {
            throw new \RuntimeException("Missing JSON: {$jsonPath}");
        }

        $rows = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // Map province name -> province row
        $provinces = DB::table('provinces')
            ->select(['id', 'name'])
            ->get()
            ->keyBy(fn($p) => $this->norm($p->name));

        DB::transaction(function () use ($rows, $provinces) {
            foreach (array_chunk($rows, 1000) as $chunk) {
                $payload = [];
                foreach ($chunk as $r) {
                    $provinceName = $r['province_name'] ?? null;
                    if (!$provinceName) continue;
                    $pkey = $this->norm($provinceName);
                    if (!isset($provinces[$pkey])) {
                        // nếu mismatch tên tỉnh giữa Excel và ProvinceSeeder, bạn có thể log ra đây để xử lý mapping alias
                        continue;
                    }
                    $payload[] = [
                        'id'         => (string)\Illuminate\Support\Str::uuid(),
                        'code'       => (string)$r['code'],
                        'name'       => (string)$r['name'],
                        'province_id'=> $provinces[$pkey]->id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                }
                if ($payload) {
                    DB::table('wards')->upsert(
                        $payload,
                        ['code'],
                        ['name', 'province_id', 'updated_at']
                    );
                }
            }
        });
    }

    private function norm(string $s): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        return mb_strtolower($s, 'UTF-8');
    }
}
