<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role;

class RoleScope extends Model
{
    use HasUuids; // UUID

    protected $fillable = [
        'role_id','employee_id','department_id'
    ]; // Phạm vi dữ liệu gắn với vai trò

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Helper: Tìm User có role trong department cụ thể
     * 
     * @param string $roleName Tên role (vd: 'Director')
     * @param string $departmentId UUID của department
     * @return User|null
     */
    public static function findUserWithRoleInDepartment(string $roleName, string $departmentId): ?User
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return null;
        }

        $roleScope = self::where('role_id', $role->id)
            ->where('department_id', $departmentId)
            ->whereNotNull('employee_id')
            ->first();

        if (!$roleScope || !$roleScope->employee_id) {
            return null;
        }

        $employee = Employee::find($roleScope->employee_id);
        return $employee?->user;
    }
}
