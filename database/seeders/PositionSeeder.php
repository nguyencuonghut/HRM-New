<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $jsonPath = database_path('data/positions.json');
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("positions.json not found at: {$jsonPath}");
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($data) || empty($data)) {
            throw new \RuntimeException("positions.json invalid or empty");
        }

        $norm = function (?string $s): string {
            $s = $s ?? '';
            $s = str_replace("\xC2\xA0", ' ', $s);
            $s = trim($s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return mb_strtolower($s);
        };

        // Load departments
        $departments = DB::table('departments')
            ->select('id', 'name', 'type', 'parent_id')
            ->get();

        $deptExact = []; // TYPE|name|parent => node
        $deptLoose = []; // TYPE|name| => [nodes]

        foreach ($departments as $d) {
            $nameKey = $norm($d->name);
            $deptExact[$d->type . '|' . $nameKey . '|' . ($d->parent_id ?? '')] = $d;
            $deptLoose[$d->type . '|' . $nameKey . '|'][] = $d;
        }

        $findNode = function (string $type, string $name, ?string $parentId = null)
            use ($deptExact, $deptLoose, $norm) {

            $nameKey = $norm($name);

            $kExact = $type . '|' . $nameKey . '|' . ($parentId ?? '');
            if (isset($deptExact[$kExact])) return $deptExact[$kExact];

            $kLoose = $type . '|' . $nameKey . '|';
            if (!empty($deptLoose[$kLoose]) && count($deptLoose[$kLoose]) === 1) {
                return $deptLoose[$kLoose][0];
            }
            return null;
        };

        // Existing positions
        $existing = DB::table('positions')->select('department_id', 'title', 'level')->get();
        $existsSet = [];
        foreach ($existing as $p) {
            $existsSet[$p->department_id . '|' . $norm($p->title) . '|' . $norm($p->level ?? '')] = true;
        }

        // Logs
        $log = [
            'invalid' => [],
            'unmatched_department' => [],
            'duplicate_existing' => [],
            'duplicate_in_json' => [],
        ];

        $rows = [];
        $jsonSeen = [];

        foreach ($data as $i => $item) {
            $title = trim((string)($item['title'] ?? ''));
            $deptName = trim((string)($item['department'] ?? ''));
            $teamName = trim((string)($item['team'] ?? ''));

            if ($title === '') {
                $log['invalid'][] = ['index' => $i, 'item' => $item];
                continue;
            }

            // resolve node
            $deptNode = $deptName !== '' ? $findNode('DEPARTMENT', $deptName) : null;

            // If not found as DEPARTMENT, try UNIT (for cases like "Bộ phận Kho")
            if (!$deptNode && $deptName !== '') {
                $deptNode = $findNode('UNIT', $deptName);
            }

            $targetNode = null;

            // Prefer child under DEPARTMENT/UNIT
            if ($deptNode && $teamName !== '') {
                $targetNode = $findNode('UNIT', $teamName, $deptNode->id)
                           ?? $findNode('TEAM', $teamName, $deptNode->id);
            }

            // Fallback to unique match globally
            if (!$targetNode && $teamName !== '') {
                $targetNode = $findNode('UNIT', $teamName)
                           ?? $findNode('TEAM', $teamName);
            }

            // If no team, use department/unit
            if (!$targetNode && $deptNode && $teamName === '') {
                $targetNode = $deptNode;
            }

            if (!$targetNode) {
                $log['unmatched_department'][] = [
                    'index' => $i,
                    'title' => $title,
                    'department' => $deptName,
                    'team' => $teamName,
                ];
                continue;
            }

            $level = null;
            $key = $targetNode->id . '|' . $norm($title) . '|' . $norm($level ?? '');

            if (isset($jsonSeen[$key])) {
                $log['duplicate_in_json'][] = [
                    'index' => $i,
                    'title' => $title,
                    'resolved_department' => $targetNode->name,
                    'resolved_type' => $targetNode->type,
                ];
                continue;
            }
            $jsonSeen[$key] = true;

            if (isset($existsSet[$key])) {
                $log['duplicate_existing'][] = [
                    'index' => $i,
                    'title' => $title,
                    'resolved_department' => $targetNode->name,
                    'resolved_type' => $targetNode->type,
                ];
                continue;
            }
            $existsSet[$key] = true;

            $rows[] = [
                'id' => (string) Str::uuid(),
                'department_id' => $targetNode->id,
                'title' => $title,
                'level' => null,

                // DEPRECATED: insurance_base_salary - Chỉ làm default gợi ý
                // Lương BHXH thực tế tính từ: minimum_wage × coefficient (position_salary_grades)
                'insurance_base_salary' => null,

                'position_salary' => 6500000,
                'competency_salary' => 6500000,
                'allowance' => 0,

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('positions')->insert($rows);
        }

        // Print console log
        dump('=== POSITION SEED SUMMARY ===');
        dump('Total JSON records:', count($data));
        dump('Inserted:', count($rows));
        dump('Invalid:', count($log['invalid']));
        dump('Unmatched department:', count($log['unmatched_department']));
        dump('Duplicate existing:', count($log['duplicate_existing']));
        dump('Duplicate in JSON:', count($log['duplicate_in_json']));

        if (!empty($log['unmatched_department'])) {
            dump('--- UNMATCHED DEPARTMENT RECORDS ---');
            dump($log['unmatched_department']);
        }
        if (!empty($log['duplicate_existing'])) {
            dump('--- DUPLICATE EXISTING RECORDS ---');
            dump($log['duplicate_existing']);
        }
        if (!empty($log['duplicate_in_json'])) {
            dump('--- DUPLICATE IN JSON RECORDS ---');
            dump($log['duplicate_in_json']);
        }
        if (!empty($log['invalid'])) {
            dump('--- INVALID RECORDS ---');
            dump($log['invalid']);
        }
    }
}
