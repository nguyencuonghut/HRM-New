<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // === Soft Skills ===
            ['name' => 'Giao tiếp hiệu quả', 'code' => 'SOFT_COMM'],
            ['name' => 'Làm việc nhóm', 'code' => 'SOFT_TEAMWORK'],
            ['name' => 'Tư duy phản biện', 'code' => 'SOFT_CRITICAL'],
            ['name' => 'Giải quyết vấn đề', 'code' => 'SOFT_PROBLEM'],
            ['name' => 'Quản lý thời gian', 'code' => 'SOFT_TIME'],
            ['name' => 'Thuyết trình', 'code' => 'SOFT_PRESENTATION'],
            ['name' => 'Đàm phán và thuyết phục', 'code' => 'SOFT_NEGOTIATION'],
            ['name' => 'Tư duy sáng tạo', 'code' => 'SOFT_CREATIVE'],

            // === Professional / Technical Skills ===
            ['name' => 'Kế toán tổng hợp', 'code' => 'PRO_ACC_GEN'],
            ['name' => 'Phân tích tài chính', 'code' => 'PRO_FIN_ANALYSIS'],
            ['name' => 'Nhân sự & Tuyển dụng', 'code' => 'PRO_HR_RECRUIT'],
            ['name' => 'Quản lý tiền lương', 'code' => 'PRO_HR_PAYROLL'],
            ['name' => 'Bán hàng chuyên nghiệp', 'code' => 'PRO_SALES'],
            ['name' => 'Chăm sóc khách hàng', 'code' => 'PRO_CUSTOMER'],
            ['name' => 'Marketing kỹ thuật số', 'code' => 'PRO_DIGIMKT'],
            ['name' => 'Thiết kế đồ họa', 'code' => 'PRO_GRAPHIC'],
            ['name' => 'Phân tích dữ liệu', 'code' => 'PRO_DATA_ANALYSIS'],
            ['name' => 'Quản lý dự án', 'code' => 'PRO_PM'],

            // === IT & Office ===
            ['name' => 'Microsoft Word', 'code' => 'IT_WORD'],
            ['name' => 'Microsoft Excel', 'code' => 'IT_EXCEL'],
            ['name' => 'Microsoft PowerPoint', 'code' => 'IT_PPT'],
            ['name' => 'Google Workspace (Docs, Sheets, Slides)', 'code' => 'IT_GWS'],
            ['name' => 'ERP / HRM System', 'code' => 'IT_HRM'],
            ['name' => 'Kỹ năng sử dụng phần mềm CRM', 'code' => 'IT_CRM'],
            ['name' => 'Lập trình PHP/Laravel', 'code' => 'IT_PHP'],
            ['name' => 'Lập trình JavaScript/Vue.js', 'code' => 'IT_JS'],
            ['name' => 'Cơ sở dữ liệu SQL', 'code' => 'IT_SQL'],
            ['name' => 'Quản trị hệ thống mạng', 'code' => 'IT_NETWORK'],

            // === Language Skills ===
            ['name' => 'Tiếng Anh giao tiếp', 'code' => 'LANG_EN'],
            ['name' => 'Tiếng Anh chuyên ngành', 'code' => 'LANG_EN_PRO'],
            ['name' => 'Tiếng Nhật (JLPT N3+)', 'code' => 'LANG_JP'],
            ['name' => 'Tiếng Hàn (TOPIK 3+)', 'code' => 'LANG_KR'],
            ['name' => 'Tiếng Trung (HSK 4+)', 'code' => 'LANG_CN'],

            // === Leadership / Management ===
            ['name' => 'Lãnh đạo đội nhóm', 'code' => 'LEAD_TEAM'],
            ['name' => 'Huấn luyện & kèm cặp nhân viên', 'code' => 'LEAD_COACH'],
            ['name' => 'Đánh giá hiệu suất', 'code' => 'LEAD_PERF'],
            ['name' => 'Xây dựng văn hóa doanh nghiệp', 'code' => 'LEAD_CULTURE'],
            ['name' => 'Ra quyết định chiến lược', 'code' => 'LEAD_DECISION'],
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['code' => $skill['code']],
                [
                    'id'   => Str::uuid(),
                    'name' => $skill['name'],
                    'code' => $skill['code'],
                ]
            );
        }
    }
}
