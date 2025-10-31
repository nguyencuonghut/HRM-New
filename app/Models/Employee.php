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
        'gender',
        'marital_status',
        'avatar',
        'cccd',
        'cccd_issued_on',
        'cccd_issued_by',
        'ward_id',
        'address_street',
        'temp_ward_id',
        'temp_address_street',
        'phone',
        'emergency_contact_phone',
        'personal_email',
        'company_email',
        'hire_date',
        'status',
        'si_number',
    ]; // Các trường cho phép gán hàng loạt

    // Quan hệ: tài khoản đăng nhập (nếu có)
    public function user() { return $this->belongsTo(\App\Models\User::class); }

    // Quan hệ: assignments (phân công/kiêm nhiệm) của nhân viên
    public function assignments() { return $this->hasMany(EmployeeAssignment::class); }

    // Quan hệ: ward thường trú (theo CCCD)
    public function ward() { return $this->belongsTo(\App\Models\Ward::class); }

    // Quan hệ: ward tạm trú
    public function tempWard() { return $this->belongsTo(\App\Models\Ward::class, 'temp_ward_id'); }
}
