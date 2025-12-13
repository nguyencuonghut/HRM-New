<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Service để tính toán số ngày nghỉ phép theo Luật Lao động Việt Nam 2019
 *
 * Mỗi loại phép có cách tính khác nhau:
 * - ANNUAL: Quota theo tháng làm việc (1 ngày/tháng)
 * - SICK: Không giới hạn (cần giấy y tế)
 * - PERSONAL_PAID: Theo sự kiện cụ thể
 * - MATERNITY: 180 ngày base + điều kiện
 * - UNPAID: Không giới hạn
 */
class LeaveCalculationService
{
    /**
     * Tính số ngày nghỉ phép riêng có lương theo sự kiện
     * Điều 115, Luật Lao động 2019
     */
    public function calculatePersonalPaidLeaveDays(string $reason): int
    {
        return match ($reason) {
            'MARRIAGE' => 3,                    // Kết hôn
            'CHILD_MARRIAGE' => 1,              // Con kết hôn
            'PARENT_DEATH' => 3,                // Bố/mẹ/vợ/chồng/con chết
            'SIBLING_DEATH' => 1,               // Ông/bà/anh/chị/em chết
            'SPOUSE_BIRTH' => 5,                // Vợ sinh con (base)
            'SPOUSE_BIRTH_TWINS' => 7,          // Vợ sinh đôi
            'SPOUSE_BIRTH_TRIPLETS' => 10,      // Vợ sinh ba
            'SPOUSE_BIRTH_CAESAREAN' => 7,      // Vợ sinh mổ
            'MOVING_HOUSE' => 1,                // Chuyển nhà
            'CHILD_SICK' => 0,                  // Con ốm (theo quy chế công ty)
            default => 0,
        };
    }

    /**
     * Tính số ngày nghỉ thai sản
     * Điều 139, Luật Lao động 2019
     *
     * LƯU Ý: 180 ngày = NGÀY DƯƠNG LỊCH (bao gồm T7, CN, Lễ)
     */
    public function calculateMaternityLeaveDays(array $conditions): int
    {
        $baseDays = 180; // 6 tháng
        $extraDays = 0;

        // Sinh đôi trở lên: +30 ngày/con từ con thứ 2
        if (isset($conditions['twins_count']) && $conditions['twins_count'] > 1) {
            $extraDays += ($conditions['twins_count'] - 1) * 30;
        }

        // Sinh mổ: +15 ngày
        if (isset($conditions['is_caesarean']) && $conditions['is_caesarean']) {
            $extraDays += 15;
        }

        // Sinh non (≤32 tuần): Toàn bộ thời gian điều trị
        // NOTE: Cần xử lý riêng vì không cố định

        // Con dưới 36 tháng tuổi: +1 tháng (30 ngày)
        if (isset($conditions['children_under_36_months']) && $conditions['children_under_36_months'] > 0) {
            $extraDays += 30;
        }

        return $baseDays + $extraDays;
    }

    /**
     * Validate giấy xác nhận y tế cho phép ốm
     * Phép ốm không có quota, nhưng BẮT BUỘC có giấy y tế
     */
    public function validateSickLeave(array $data): array
    {
        $valid = true;
        $message = '';

        // Bắt buộc có giấy xác nhận y tế
        if (empty($data['medical_certificate_path'])) {
            $valid = false;
            $message = 'Phép ốm yêu cầu giấy xác nhận của cơ sở y tế';
        }

        // Nếu nghỉ > 30 ngày, cảnh báo về quy định BHXH
        if (isset($data['days']) && $data['days'] > 30) {
            $message .= ' Lưu ý: Công ty chỉ trả lương tối đa 30 ngày đầu. Từ ngày 31, BHXH sẽ chi trả';
        }

        return [
            'valid' => $valid,
            'message' => $message,
        ];
    }

    /**
     * Validate phép riêng có lương
     * Cần chọn lý do cụ thể để tính số ngày
     */
    public function validatePersonalPaidLeave(array $data): array
    {
        $valid = true;
        $message = '';

        if (empty($data['personal_leave_reason'])) {
            $valid = false;
            $message = 'Vui lòng chọn lý do nghỉ phép riêng';
            return ['valid' => $valid, 'message' => $message];
        }

        // Tính số ngày tự động theo lý do
        $allowedDays = $this->calculatePersonalPaidLeaveDays($data['personal_leave_reason']);
        if ($allowedDays == 0) {
            $valid = false;
            $message = 'Lý do không hợp lệ hoặc cần phê duyệt đặc biệt';
        }

        if (isset($data['days']) && $data['days'] > $allowedDays) {
            $valid = false;
            $message = "Lý do '{$data['personal_leave_reason']}' chỉ được nghỉ tối đa {$allowedDays} ngày";
        }

        return [
            'valid' => $valid,
            'message' => $message,
        ];
    }

    /**
     * Validate phép thai sản
     * Chỉ áp dụng cho nhân viên nữ
     */
    public function validateMaternityLeave(Employee $employee, array $data): array
    {
        $valid = true;
        $message = '';

        if ($employee->gender !== 'FEMALE') {
            $valid = false;
            $message = 'Phép thai sản chỉ áp dụng cho nhân viên nữ';
            return ['valid' => $valid, 'message' => $message];
        }

        if (empty($data['expected_due_date'])) {
            $valid = false;
            $message = 'Vui lòng nhập ngày dự kiến sinh';
            return ['valid' => $valid, 'message' => $message];
        }

        // Có thể nghỉ trước sinh tối đa 2 tháng
        if (isset($data['start_date']) && isset($data['expected_due_date'])) {
            $start = Carbon::parse($data['start_date']);
            $dueDate = Carbon::parse($data['expected_due_date']);
            $daysBefore = $dueDate->diffInDays($start, false);

            if ($daysBefore > 60) {
                $valid = false;
                $message = 'Chỉ được nghỉ trước sinh tối đa 60 ngày (2 tháng)';
            }
        }

        return [
            'valid' => $valid,
            'message' => $message,
        ];
    }

    /**
     * Get list of personal paid leave reasons with days
     */
    public function getPersonalPaidLeaveReasons(): array
    {
        return [
            ['value' => 'MARRIAGE', 'label' => 'Kết hôn', 'days' => 3],
            ['value' => 'CHILD_MARRIAGE', 'label' => 'Con kết hôn', 'days' => 1],
            ['value' => 'PARENT_DEATH', 'label' => 'Bố/Mẹ/Vợ/Chồng/Con chết', 'days' => 3],
            ['value' => 'SIBLING_DEATH', 'label' => 'Ông/Bà/Anh/Chị/Em chết', 'days' => 1],
            ['value' => 'SPOUSE_BIRTH', 'label' => 'Vợ sinh con', 'days' => 5],
            ['value' => 'SPOUSE_BIRTH_TWINS', 'label' => 'Vợ sinh đôi', 'days' => 7],
            ['value' => 'SPOUSE_BIRTH_CAESAREAN', 'label' => 'Vợ sinh mổ', 'days' => 7],
            ['value' => 'MOVING_HOUSE', 'label' => 'Chuyển nhà', 'days' => 1],
        ];
    }
}
