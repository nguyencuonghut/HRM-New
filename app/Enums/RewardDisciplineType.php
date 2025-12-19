<?php

namespace App\Enums;

enum RewardDisciplineType: string
{
    case REWARD = 'REWARD';
    case DISCIPLINE = 'DISCIPLINE';

    public function label(): string
    {
        return match($this) {
            self::REWARD => 'Khen thưởng',
            self::DISCIPLINE => 'Kỷ luật',
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::REWARD => 'success',
            self::DISCIPLINE => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::REWARD => 'pi pi-star-fill',
            self::DISCIPLINE => 'pi pi-exclamation-triangle',
        };
    }
}
