<?php

namespace Database\Seeders;

use App\Models\CompanyHoliday;
use Illuminate\Database\Seeder;

class CompanyHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays2026 = [
            // Tết Nguyên Đán 2026 (29/12 âm lịch = 28/01/2026 dương lịch)
            [
                'name' => 'Tết Nguyên Đán (Mùng 1)',
                'holiday_date' => '2026-01-29',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false, // Tết âm lịch thay đổi hàng năm
                'note' => 'Nghỉ Tết Nguyên Đán Bính Ngọ',
            ],
            [
                'name' => 'Tết Nguyên Đán (Mùng 2)',
                'holiday_date' => '2026-01-30',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false,
            ],
            [
                'name' => 'Tết Nguyên Đán (Mùng 3)',
                'holiday_date' => '2026-01-31',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false,
            ],
            [
                'name' => 'Tết Nguyên Đán (Mùng 4)',
                'holiday_date' => '2026-02-01',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false,
            ],
            [
                'name' => 'Tết Nguyên Đán (Mùng 5)',
                'holiday_date' => '2026-02-02',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false,
            ],

            // Giỗ Tổ Hùng Vương (10/3 âm lịch = 28/04/2026 dương lịch)
            [
                'name' => 'Giỗ Tổ Hùng Vương',
                'holiday_date' => '2026-04-28',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => false, // Âm lịch thay đổi
                'note' => '10/3 âm lịch',
            ],

            // Ngày Chiến thắng 30/4
            [
                'name' => 'Ngày Giải phóng miền Nam',
                'holiday_date' => '2026-04-30',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => true, // Dương lịch cố định
                'note' => 'Ngày Chiến thắng 30/4/1975',
            ],

            // Ngày Quốc tế Lao động 1/5
            [
                'name' => 'Ngày Quốc tế Lao động',
                'holiday_date' => '2026-05-01',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => true, // Dương lịch cố định
                'note' => 'International Workers\' Day',
            ],

            // Ngày Quốc khánh 2/9
            [
                'name' => 'Ngày Quốc khánh',
                'holiday_date' => '2026-09-02',
                'year' => 2026,
                'is_compensated' => false,
                'is_recurring' => true, // Dương lịch cố định
                'note' => 'Quốc khánh nước Cộng hòa Xã hội chủ nghĩa Việt Nam',
            ],

            // Nghỉ bù nếu ngày lễ trùng cuối tuần (ví dụ)
            // 30/4/2026 là Thứ 5, 1/5 là Thứ 6, 2/9 là Thứ 4 - không cần nghỉ bù

        ];

        foreach ($holidays2026 as $holiday) {
            CompanyHoliday::create($holiday);
        }

        $this->command->info('✓ Created ' . count($holidays2026) . ' company holidays for 2026');
    }
}
