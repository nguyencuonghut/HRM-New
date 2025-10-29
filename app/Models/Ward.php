<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ward extends Model
{
    use HasUuids; // UUID

    protected $fillable = ['province_id', 'code', 'name']; // Các trường cho phép gán hàng loạt

    // Quan hệ: thuộc tỉnh/thành nào
    public function province() { return $this->belongsTo(Province::class); }

    // Quan hệ: có thể có nhiều nhân viên thuộc xã/phường này
    public function employees() { return $this->hasMany(Employee::class); }
}
