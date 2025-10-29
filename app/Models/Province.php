<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Province extends Model
{
    use HasUuids; // UUID

    protected $fillable = ['code', 'name']; // Các trường cho phép gán hàng loạt

    // Quan hệ: 1 tỉnh có nhiều xã/phường
    public function wards() { return $this->hasMany(Ward::class); }
}
