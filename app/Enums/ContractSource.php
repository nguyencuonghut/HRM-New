<?php
namespace App\Enums;

enum ContractSource: string {
    case LEGACY='LEGACY'; case RECRUITMENT='RECRUITMENT';

    public function label(): string {
        return match($this) {
            self::LEGACY => 'Nhập lại (backfill)',
            self::RECRUITMENT => 'Từ tuyển dụng',
        };
    }
}
