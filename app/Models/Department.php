<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Department extends Model
{
    use HasUuids; // UUID cho khóa chính

    protected $fillable = [
        'type', 'parent_id','name','code','head_assignment_id','deputy_assignment_id','order_index','is_active'
    ]; // Các trường cho phép gán hàng loạt

    // Node cha trong cây
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }

    // Các node con
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    // Các phân công trong đơn vị
    public function assignments() { return $this->hasMany(EmployeeAssignment::class, 'department_id'); }
}
