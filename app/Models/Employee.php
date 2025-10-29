<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasUuids; // UUID cho khóa chính

    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'dob',
        'cccd',
        'cccd_issued_on',
        'cccd_issued_by',
        'ward_id',
        'address_street',
        'phone',
        'personal_email',
        'hire_date',
        'status',
        'si_number',
    ]; // Các trường cho phép gán hàng loạt

    // Quan hệ: tài khoản đăng nhập (nếu có)
    public function user() { return $this->belongsTo(\App\Models\User::class); }

    // Quan hệ: assignments (phân công/kiêm nhiệm) của nhân viên
    public function assignments() { return $this->hasMany(EmployeeAssignment::class); }

    // (Tuỳ chọn) ward nếu có danh mục hành chính
    public function ward() { return $this->belongsTo(\App\Models\Ward::class); }
}
