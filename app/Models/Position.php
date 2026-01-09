<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\InsuranceSalaryCategory;

class Position extends Model
{
    use HasUuids; // UUID cho khóa chính

    protected $fillable = [
        'department_id','title','level','insurance_base_salary','position_salary','competency_salary', 'allowance',
        'insurance_salary_category_id' // FK to insurance_salary_categories
    ]; // Cho phép gán hàng loạt

    // Quan hệ: thuộc phòng ban nào
    public function department() { return $this->belongsTo(Department::class); }

    // Quan hệ: thuộc nhóm chức danh BHXH nào
    public function insuranceSalaryCategory() { return $this->belongsTo(InsuranceSalaryCategory::class, 'insurance_salary_category_id'); }

    // Quan hệ: có nhiều employee assignments
    public function employeeAssignments() { return $this->hasMany(EmployeeAssignment::class); }

    // Quan hệ: thang bậc lương BHXH
    public function salaryGrades() { return $this->hasMany(PositionSalaryGrade::class); }
}
