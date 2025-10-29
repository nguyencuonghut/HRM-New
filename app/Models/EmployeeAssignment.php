<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeAssignment extends Model
{
    use HasUuids; // UUID

    protected $fillable = [
        'employee_id','department_id','position_id','is_primary','role_type','start_date','end_date','status'
    ]; // Cho phép gán hàng loạt
}
