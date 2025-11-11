<?php
namespace App\Enums;

enum ContractStatus: string {
    case DRAFT='DRAFT'; case PENDING_APPROVAL='PENDING_APPROVAL'; case ACTIVE='ACTIVE';
    case REJECTED='REJECTED'; case SUSPENDED='SUSPENDED'; case TERMINATED='TERMINATED';
    case EXPIRED='EXPIRED'; case CANCELLED='CANCELLED';

    public function label(): string {
        return match($this) {
            self::DRAFT => 'Nháp',
            self::PENDING_APPROVAL => 'Chờ duyệt',
            self::ACTIVE => 'Hiệu lực',
            self::REJECTED => 'Từ chối',
            self::SUSPENDED => 'Tạm hoãn',
            self::TERMINATED => 'Chấm dứt',
            self::EXPIRED => 'Hết hạn',
            self::CANCELLED => 'Hủy',
        };
    }
}
