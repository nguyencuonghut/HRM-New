<?php

namespace App\Enums;

enum AppendixType: string
{
    case SALARY = 'SALARY';
    case ALLOWANCE = 'ALLOWANCE';
    case POSITION = 'POSITION';
    case DEPARTMENT = 'DEPARTMENT';
    case WORKING_TERMS = 'WORKING_TERMS';
    case EXTENSION = 'EXTENSION';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match($this) {
            self::SALARY => 'Thay đổi lương',
            self::ALLOWANCE => 'Thay đổi phụ cấp',
            self::POSITION => 'Thay đổi chức danh',
            self::DEPARTMENT => 'Điều chuyển đơn vị',
            self::WORKING_TERMS => 'Thay đổi điều kiện làm việc',
            self::EXTENSION => 'Gia hạn hợp đồng',
            self::OTHER => 'Khác',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SALARY => 'Phụ lục điều chỉnh mức lương cơ bản',
            self::ALLOWANCE => 'Phụ lục điều chỉnh các khoản phụ cấp',
            self::POSITION => 'Phụ lục thay đổi chức danh, vị trí công tác',
            self::DEPARTMENT => 'Phụ lục điều chuyển đơn vị, phòng ban',
            self::WORKING_TERMS => 'Phụ lục thay đổi thời gian làm việc, địa điểm',
            self::EXTENSION => 'Phụ lục gia hạn thời hạn hợp đồng',
            self::OTHER => 'Phụ lục thay đổi các nội dung khác',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SALARY => 'pi-money-bill',
            self::ALLOWANCE => 'pi-wallet',
            self::POSITION => 'pi-briefcase',
            self::DEPARTMENT => 'pi-building',
            self::WORKING_TERMS => 'pi-clock',
            self::EXTENSION => 'pi-refresh',
            self::OTHER => 'pi-file',
        };
    }
}
