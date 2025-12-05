<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Phép năm',
                'code' => 'ANNUAL',
                'color' => '#10b981', // green
                'days_per_year' => 12,
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 1,
                'description' => 'Nghỉ phép năm theo quy định (12 ngày/năm)',
            ],
            [
                'name' => 'Phép ốm',
                'code' => 'SICK',
                'color' => '#ef4444', // red
                'days_per_year' => 30,
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 2,
                'description' => 'Nghỉ ốm đau có giấy xác nhận của cơ sở y tế',
            ],
            [
                'name' => 'Phép riêng có lương',
                'code' => 'PERSONAL_PAID',
                'color' => '#3b82f6', // blue
                'days_per_year' => 3,
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 3,
                'description' => 'Phép riêng: kết hôn, tang lễ, việc gia đình... (có lương)',
            ],
            [
                'name' => 'Phép không lương',
                'code' => 'UNPAID',
                'color' => '#6b7280', // gray
                'days_per_year' => 0,
                'requires_approval' => true,
                'is_paid' => false,
                'is_active' => true,
                'order_index' => 4,
                'description' => 'Nghỉ không lương theo yêu cầu cá nhân',
            ],
            [
                'name' => 'Phép thai sản',
                'code' => 'MATERNITY',
                'color' => '#ec4899', // pink
                'days_per_year' => 180,
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 5,
                'description' => 'Nghỉ thai sản theo quy định pháp luật (6 tháng)',
            ],
            [
                'name' => 'Phép học tập',
                'code' => 'STUDY',
                'color' => '#8b5cf6', // purple
                'days_per_year' => 0,
                'requires_approval' => true,
                'is_paid' => false,
                'is_active' => true,
                'order_index' => 6,
                'description' => 'Nghỉ để học tập, thi cử',
            ],
            [
                'name' => 'Phép công tác',
                'code' => 'BUSINESS',
                'color' => '#f59e0b', // amber
                'days_per_year' => 0,
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 7,
                'description' => 'Công tác tại đơn vị khác hoặc ra ngoài',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }
    }
}
