<?php

namespace App\Enums;

enum ApprovalLevel: string
{
    case MANAGER = 'MANAGER';
    case DIRECTOR = 'DIRECTOR';

    public function label(): string
    {
        return match($this) {
            self::MANAGER => 'Trưởng phòng',
            self::DIRECTOR => 'Giám đốc',
        };
    }

    public function order(): int
    {
        return match($this) {
            self::MANAGER => 1,
            self::DIRECTOR => 2,
        };
    }

    public static function fromOrder(int $order): ?self
    {
        return match($order) {
            1 => self::MANAGER,
            2 => self::DIRECTOR,
            default => null,
        };
    }
}
