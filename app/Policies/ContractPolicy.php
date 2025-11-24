<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contract;
use App\Services\ContractApprovalService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool    { return $user->hasPermissionTo('view contracts'); }
    public function view(User $user, Contract $c): bool { return $user->hasPermissionTo('view contracts'); }
    public function create(User $user): bool     { return $user->hasPermissionTo('create contracts'); }
    public function update(User $user, Contract $c): bool { return $user->hasPermissionTo('edit contracts'); }
    public function delete(User $user, Contract $c): bool { return $user->hasPermissionTo('delete contracts'); }
    public function bulkDelete(User $user): bool { return $user->hasPermissionTo('delete contracts'); }

    /**
     * Quyền submit hợp đồng để phê duyệt (HR, người tạo)
     */
    public function submit(User $user, Contract $contract): bool
    {
        return $user->hasPermissionTo('create contracts') && $contract->isDraft();
    }

    /**
     * Quyền phê duyệt - dựa trên workflow và role
     */
    public function approve(User $user, Contract $contract): bool
    {
        // Phải có permission 'approve contracts'
        if (!$user->hasPermissionTo('approve contracts')) {
            return false;
        }

        // Admin luôn có quyền
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Kiểm tra theo workflow (user có phải là approver của step hiện tại không)
        $approvalService = app(ContractApprovalService::class);
        return $approvalService->canUserApprove($contract, $user);
    }

    /**
     * Quyền thu hồi yêu cầu phê duyệt
     */
    public function recall(User $user, Contract $contract): bool
    {
        // Chỉ Admin hoặc người tạo mới thu hồi được
        return $user->hasRole('Admin') || $user->hasPermissionTo('create contracts');
    }
}
