<?php
namespace App\Enums;

enum ContractType: string {
    case PROBATION  = 'PROBATION';
    case FIXED_TERM = 'FIXED_TERM';
    case INDEFINITE = 'INDEFINITE';
    case SERVICE    = 'SERVICE';
    case INTERNSHIP = 'INTERNSHIP';
    case PARTTIME   = 'PARTTIME';

    public function label(): string {
        return match($this) {
            self::PROBATION  => 'Thử việc',
            self::FIXED_TERM => 'Xác định thời hạn',
            self::INDEFINITE => 'Không xác định thời hạn',
            self::SERVICE    => 'Dịch vụ',
            self::INTERNSHIP => 'Thực tập',
            self::PARTTIME   => 'Bán thời gian',
        };
    }
}
