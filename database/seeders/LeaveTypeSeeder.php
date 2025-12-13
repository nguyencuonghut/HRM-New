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
                'description' => 'Nghỉ phép năm (12 ngày/năm + thâm niên, tính theo ngày làm việc)',
            ],
            [
                'name' => 'Phép ốm',
                'code' => 'SICK',
                'color' => '#ef4444', // red
                'days_per_year' => 0, // ✅ Không giới hạn, cần giấy y tế
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 2,
                'description' => 'Nghỉ ốm với giấy xác nhận y tế (không giới hạn ngày, công ty trả lương tối đa 30 ngày, BHXH chi trả sau đó)',
            ],
            [
                'name' => 'Phép riêng có lương',
                'code' => 'PERSONAL_PAID',
                'color' => '#3b82f6', // blue
                'days_per_year' => 0, // ✅ Tính theo từng sự kiện, không phải quota
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 3,
                'description' => 'Phép riêng theo sự kiện: Kết hôn (3 ngày), Con kết hôn (1 ngày), Tang lễ (1-3 ngày tùy quan hệ), Vợ sinh (5-14 ngày), Chuyển nhà (1 ngày)',
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
                'description' => 'Nghỉ không lương theo thỏa thuận với công ty (không giới hạn)',
            ],
            [
                'name' => 'Phép thai sản',
                'code' => 'MATERNITY',
                'color' => '#ec4899', // pink
                'days_per_year' => 0, // ✅ Tính theo tình huống sinh nở
                'requires_approval' => true,
                'is_paid' => true,
                'is_active' => true,
                'order_index' => 5,
                'description' => 'Nghỉ thai sản: 180 ngày (6 tháng, tính cả T7/CN/lễ), +30 ngày nếu sinh đôi, +15 ngày nếu mổ. BHXH chi trả 100% lương',
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
                'description' => 'Nghỉ học tập, thi cử (theo quy chế công ty)',
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
                'description' => 'Công tác ngoài văn phòng (tính là ngày làm việc, không phải nghỉ phép)',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['code' => $leaveType['code']],
                $leaveType
            );
        }
    }
}
