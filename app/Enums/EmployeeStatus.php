<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ON_LEAVE = 'ON_LEAVE';
    case INACTIVE = 'INACTIVE';
    case TERMINATED = 'TERMINATED';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Đang làm việc',
            self::ON_LEAVE => 'Đang nghỉ dài ngày',
            self::INACTIVE => 'Ngừng hoạt động',
            self::TERMINATED => 'Đã nghỉ việc',
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::ON_LEAVE => 'warn',
            self::INACTIVE => 'secondary',
            self::TERMINATED => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ACTIVE => 'pi-check-circle',
            self::ON_LEAVE => 'pi-clock',
            self::INACTIVE => 'pi-pause-circle',
            self::TERMINATED => 'pi-times-circle',
        };
    }
}
