<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RoleScope extends Model
{
    use HasUuids; // UUID

    protected $fillable = [
        'role_id','employee_id','department_id'
    ]; // Phạm vi dữ liệu gắn với vai trò
}
