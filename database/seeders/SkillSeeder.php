<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Skill;
use App\Models\SkillCategory;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        // Get categories
        $categories = [
            'office' => SkillCategory::where('name', 'Tin học văn phòng')->first(),
            'programming' => SkillCategory::where('name', 'Lập trình')->first(),
            'language' => SkillCategory::where('name', 'Ngoại ngữ')->first(),
            'professional' => SkillCategory::where('name', 'Chuyên môn nghề nghiệp')->first(),
            'soft' => SkillCategory::where('name', 'Kỹ năng mềm')->first(),
        ];

        $skills = [
            // === Tin học văn phòng ===
            ['name' => 'Microsoft Word', 'code' => 'IT_WORD', 'category_id' => $categories['office']?->id],
            ['name' => 'Microsoft Excel', 'code' => 'IT_EXCEL', 'category_id' => $categories['office']?->id],
            ['name' => 'Microsoft PowerPoint', 'code' => 'IT_PPT', 'category_id' => $categories['office']?->id],
            ['name' => 'Google Workspace (Docs, Sheets, Slides)', 'code' => 'IT_GWS', 'category_id' => $categories['office']?->id],
            ['name' => 'ERP / HRM System', 'code' => 'IT_HRM', 'category_id' => $categories['office']?->id],
            ['name' => 'Kỹ năng sử dụng phần mềm CRM', 'code' => 'IT_CRM', 'category_id' => $categories['office']?->id],

            // === Lập trình ===
            ['name' => 'Lập trình PHP/Laravel', 'code' => 'IT_PHP', 'category_id' => $categories['programming']?->id],
            ['name' => 'Lập trình JavaScript/Vue.js', 'code' => 'IT_JS', 'category_id' => $categories['programming']?->id],
            ['name' => 'Cơ sở dữ liệu SQL', 'code' => 'IT_SQL', 'category_id' => $categories['programming']?->id],
            ['name' => 'Quản trị hệ thống mạng', 'code' => 'IT_NETWORK', 'category_id' => $categories['programming']?->id],
            ['name' => 'Phân tích dữ liệu', 'code' => 'PRO_DATA_ANALYSIS', 'category_id' => $categories['programming']?->id],

            // === Ngoại ngữ ===
            ['name' => 'Tiếng Anh giao tiếp', 'code' => 'LANG_EN', 'category_id' => $categories['language']?->id],
            ['name' => 'Tiếng Anh chuyên ngành', 'code' => 'LANG_EN_PRO', 'category_id' => $categories['language']?->id],
            ['name' => 'Tiếng Nhật (JLPT N3+)', 'code' => 'LANG_JP', 'category_id' => $categories['language']?->id],
            ['name' => 'Tiếng Hàn (TOPIK 3+)', 'code' => 'LANG_KR', 'category_id' => $categories['language']?->id],
            ['name' => 'Tiếng Trung (HSK 4+)', 'code' => 'LANG_CN', 'category_id' => $categories['language']?->id],

            // === Chuyên môn nghề nghiệp ===
            ['name' => 'Kế toán tổng hợp', 'code' => 'PRO_ACC_GEN', 'category_id' => $categories['professional']?->id],
            ['name' => 'Phân tích tài chính', 'code' => 'PRO_FIN_ANALYSIS', 'category_id' => $categories['professional']?->id],
            ['name' => 'Nhân sự & Tuyển dụng', 'code' => 'PRO_HR_RECRUIT', 'category_id' => $categories['professional']?->id],
            ['name' => 'Quản lý tiền lương', 'code' => 'PRO_HR_PAYROLL', 'category_id' => $categories['professional']?->id],
            ['name' => 'Bán hàng chuyên nghiệp', 'code' => 'PRO_SALES', 'category_id' => $categories['professional']?->id],
            ['name' => 'Chăm sóc khách hàng', 'code' => 'PRO_CUSTOMER', 'category_id' => $categories['professional']?->id],
            ['name' => 'Marketing kỹ thuật số', 'code' => 'PRO_DIGIMKT', 'category_id' => $categories['professional']?->id],
            ['name' => 'Thiết kế đồ họa', 'code' => 'PRO_GRAPHIC', 'category_id' => $categories['professional']?->id],
            ['name' => 'Quản lý dự án', 'code' => 'PRO_PM', 'category_id' => $categories['professional']?->id],

            // === Kỹ năng mềm ===
            ['name' => 'Giao tiếp hiệu quả', 'code' => 'SOFT_COMM', 'category_id' => $categories['soft']?->id],
            ['name' => 'Làm việc nhóm', 'code' => 'SOFT_TEAMWORK', 'category_id' => $categories['soft']?->id],
            ['name' => 'Tư duy phản biện', 'code' => 'SOFT_CRITICAL', 'category_id' => $categories['soft']?->id],
            ['name' => 'Giải quyết vấn đề', 'code' => 'SOFT_PROBLEM', 'category_id' => $categories['soft']?->id],
            ['name' => 'Quản lý thời gian', 'code' => 'SOFT_TIME', 'category_id' => $categories['soft']?->id],
            ['name' => 'Thuyết trình', 'code' => 'SOFT_PRESENTATION', 'category_id' => $categories['soft']?->id],
            ['name' => 'Đàm phán và thuyết phục', 'code' => 'SOFT_NEGOTIATION', 'category_id' => $categories['soft']?->id],
            ['name' => 'Tư duy sáng tạo', 'code' => 'SOFT_CREATIVE', 'category_id' => $categories['soft']?->id],
            ['name' => 'Lãnh đạo đội nhóm', 'code' => 'LEAD_TEAM', 'category_id' => $categories['soft']?->id],
            ['name' => 'Huấn luyện & kèm cặp nhân viên', 'code' => 'LEAD_COACH', 'category_id' => $categories['soft']?->id],
            ['name' => 'Đánh giá hiệu suất', 'code' => 'LEAD_PERF', 'category_id' => $categories['soft']?->id],
            ['name' => 'Xây dựng văn hóa doanh nghiệp', 'code' => 'LEAD_CULTURE', 'category_id' => $categories['soft']?->id],
            ['name' => 'Ra quyết định chiến lược', 'code' => 'LEAD_DECISION', 'category_id' => $categories['soft']?->id],
        ];

        foreach ($skills as $skill) {
            $existing = Skill::where('code', $skill['code'])->first();

            if ($existing) {
                // Update existing skill
                $existing->update([
                    'name' => $skill['name'],
                    'category_id' => $skill['category_id'],
                ]);
            } else {
                // Create new skill
                Skill::create([
                    'id'   => Str::uuid(),
                    'name' => $skill['name'],
                    'code' => $skill['code'],
                    'category_id' => $skill['category_id'],
                ]);
            }
        }

        $this->command->info('Skills seeded with categories successfully!');
    }
}
