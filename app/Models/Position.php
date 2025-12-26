<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Position extends Model
{
    use HasUuids; // UUID cho khóa chính

    protected $fillable = [
        'department_id','title','level','insurance_base_salary','position_salary','competency_salary', 'allowance'
    ]; // Cho phép gán hàng loạt

    // Quan hệ: thuộc phòng ban nào
    public function department() { return $this->belongsTo(Department::class); }

    // Quan hệ: có nhiều employee assignments
    public function employeeAssignments() { return $this->hasMany(EmployeeAssignment::class); }

    // Quan hệ: thang bậc lương BHXH
    public function salaryGrades() { return $this->hasMany(PositionSalaryGrade::class); }
}
