<?php

namespace App\Enums;

enum RewardDisciplineStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Nháp',
            self::ACTIVE => 'Đã lưu',
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::ACTIVE => 'success',
        };
    }
}
