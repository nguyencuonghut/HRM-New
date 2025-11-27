<?php

namespace App\Enums;

enum ContractTerminationReason: string
{
    case EXPIRATION = 'EXPIRATION';                // Hết hạn tự nhiên
    case MUTUAL_AGREEMENT = 'MUTUAL';              // Thỏa thuận 2 bên
    case EMPLOYEE_RESIGNATION = 'RESIGNATION';     // Nhân viên xin nghỉ
    case EMPLOYER_TERMINATION = 'DISMISSAL';       // Công ty chấm dứt
    case PROBATION_FAILED = 'PROBATION_FAILED';    // Không qua thử việc
    case CONTRACT_BREACH = 'BREACH';               // Vi phạm hợp đồng
    case FORCE_MAJEURE = 'FORCE_MAJEURE';          // Bất khả kháng (thiên tai, dịch bệnh...)
    case RETIREMENT = 'RETIREMENT';                // Nghỉ hưu
    case DECEASED = 'DECEASED';                    // Nhân viên qua đời
    case OTHER = 'OTHER';                          // Lý do khác

    /**
     * Get label tiếng Việt
     */
    public function label(): string
    {
        return match($this) {
            self::EXPIRATION => 'Hết hạn hợp đồng',
            self::MUTUAL_AGREEMENT => 'Thỏa thuận hai bên',
            self::EMPLOYEE_RESIGNATION => 'Nhân viên xin nghỉ việc',
            self::EMPLOYER_TERMINATION => 'Công ty chấm dứt hợp đồng',
            self::PROBATION_FAILED => 'Không qua thử việc',
            self::CONTRACT_BREACH => 'Vi phạm hợp đồng',
            self::FORCE_MAJEURE => 'Bất khả kháng',
            self::RETIREMENT => 'Nghỉ hưu',
            self::DECEASED => 'Nhân viên qua đời',
            self::OTHER => 'Lý do khác',
        };
    }

    /**
     * Get all options for dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    /**
     * Check if requires severance pay
     */
    public function requiresSeverancePay(): bool
    {
        return in_array($this, [
            self::EMPLOYER_TERMINATION,
            self::FORCE_MAJEURE,
            self::EXPIRATION, // Nếu HĐ xác định thời hạn hết hạn và không gia hạn
        ]);
    }
}
