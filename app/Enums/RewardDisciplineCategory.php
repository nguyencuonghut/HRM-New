<?php

namespace App\Enums;

enum RewardDisciplineCategory: string
{
    // REWARD categories
    case BONUS = 'BONUS';
    case COMMENDATION = 'COMMENDATION';
    case CERTIFICATE = 'CERTIFICATE';
    case PROMOTION_REWARD = 'PROMOTION_REWARD';
    case ACHIEVEMENT_AWARD = 'ACHIEVEMENT_AWARD';

    // DISCIPLINE categories
    case VERBAL_WARNING = 'VERBAL_WARNING';
    case WRITTEN_WARNING = 'WRITTEN_WARNING';
    case SALARY_DEDUCTION = 'SALARY_DEDUCTION';
    case SUSPENSION = 'SUSPENSION';
    case DEMOTION = 'DEMOTION';
    case TERMINATION = 'TERMINATION';

    public function label(): string
    {
        return match($this) {
            self::BONUS => 'Thưởng tiền',
            self::COMMENDATION => 'Khen thưởng',
            self::CERTIFICATE => 'Giấy chứng nhận',
            self::PROMOTION_REWARD => 'Thưởng thăng chức',
            self::ACHIEVEMENT_AWARD => 'Danh hiệu',
            self::VERBAL_WARNING => 'Nhắc nhở miệng',
            self::WRITTEN_WARNING => 'Cảnh cáo văn bản',
            self::SALARY_DEDUCTION => 'Phạt tiền',
            self::SUSPENSION => 'Đình chỉ công việc',
            self::DEMOTION => 'Giáng chức',
            self::TERMINATION => 'Buộc thôi việc',
        };
    }

    public function type(): RewardDisciplineType
    {
        return match($this) {
            self::BONUS, self::COMMENDATION, self::CERTIFICATE,
            self::PROMOTION_REWARD, self::ACHIEVEMENT_AWARD
                => RewardDisciplineType::REWARD,
            default => RewardDisciplineType::DISCIPLINE,
        };
    }

    public function requiresAmount(): bool
    {
        return in_array($this, [
            self::BONUS,
            self::SALARY_DEDUCTION,
        ]);
    }

    public static function rewardCategories(): array
    {
        return [
            self::BONUS,
            self::COMMENDATION,
            self::CERTIFICATE,
            self::PROMOTION_REWARD,
            self::ACHIEVEMENT_AWARD,
        ];
    }

    public static function disciplineCategories(): array
    {
        return [
            self::VERBAL_WARNING,
            self::WRITTEN_WARNING,
            self::SALARY_DEDUCTION,
            self::SUSPENSION,
            self::DEMOTION,
            self::TERMINATION,
        ];
    }
}
