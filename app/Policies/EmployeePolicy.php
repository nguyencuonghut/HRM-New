<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine if user can view any employees
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view employees');
    }

    /**
     * Determine if user can view a specific employee
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view employees');
    }

    /**
     * Determine if user can create employees
     */
    public function create(User $user): bool
    {
        return $user->can('create employees');
    }

    /**
     * Determine if user can update a employee
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can('edit employees');
    }

    /**
     * Determine if user can delete a employee
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('delete employees');
    }

    public function viewProfile(User $user, Employee $employee): bool
    {
        // Quyền xem profile nhân viên
        // Admin/Director/Manager xem được; Employee chỉ xem chính mình
        if ($user->hasPermissionTo('view employees')) return true;
        return $user->employee_id === $employee->id;
    }

    public function editProfile(User $user, Employee $employee): bool
    {
        // Admin/Manager chỉnh sửa (tuỳ chính sách phòng ban), ví dụ đơn giản:
        return $user->hasPermissionTo('edit employees');
    }

    /**
     * Kiểm tra một bản ghi con (education/relative/experience/skill) có thuộc employee này không
     */
    public function ownEmployeeItem(User $user, Employee $employee, $child): bool
    {
        return $child->employee_id === $employee->id;
    }
}
