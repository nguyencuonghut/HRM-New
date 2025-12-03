<?php

namespace Database\Seeders;

use App\Models\SkillCategory;
use Illuminate\Database\Seeder;

class SkillCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tin học văn phòng',
                'description' => 'Các kỹ năng sử dụng phần mềm văn phòng như MS Office, Google Suite',
                'order_index' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Lập trình',
                'description' => 'Các ngôn ngữ lập trình và framework',
                'order_index' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Ngoại ngữ',
                'description' => 'Các ngoại ngữ như Tiếng Anh, Tiếng Nhật, Tiếng Hàn...',
                'order_index' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Chuyên môn nghề nghiệp',
                'description' => 'Kỹ năng chuyên môn theo ngành nghề (Kế toán, Marketing, HR...)',
                'order_index' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Kỹ năng mềm',
                'description' => 'Lãnh đạo, Quản lý dự án, Giao tiếp, Thuyết trình...',
                'order_index' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            SkillCategory::create($category);
        }

        $this->command->info('Skill categories seeded successfully!');
    }
}
