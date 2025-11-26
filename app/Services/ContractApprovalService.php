<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractApproval;
use App\Models\User;
use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Events\{ContractSubmitted, ContractApproved, ContractRejected};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractApprovalService
{
    /**
     * Gửi hợp đồng để phê duyệt - khởi tạo workflow
     */
    public function submitForApproval(Contract $contract): void
    {
        if (!$contract->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể gửi phê duyệt hợp đồng ở trạng thái Nháp.'
            ]);
        }

        DB::transaction(function () use ($contract) {
            // Tạo approval steps theo thứ tự: Manager -> Director
            $this->createApprovalSteps($contract);

            // Cập nhật status hợp đồng
            $contract->update(['status' => ContractStatus::PENDING_APPROVAL->value]);

            // Log activity
            activity('contract')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties([
                    'contract_number' => $contract->contract_number,
                    'action' => 'submitted_for_approval',
                ])
                ->log('Gửi phê duyệt');
            event(new ContractSubmitted($contract));
        });
    }

    /**
     * Tạo các bước phê duyệt
     */
    protected function createApprovalSteps(Contract $contract): void
    {
        // Chỉ có Director approval
        $director = $this->findDirectorForContract($contract);
        ContractApproval::create([
            'contract_id' => $contract->id,
            'level' => ApprovalLevel::DIRECTOR,
            'order' => 1,
            'approver_id' => $director?->id,
            'status' => ApprovalStatus::PENDING,
        ]);
    }

    /**
     * Phê duyệt hợp đồng tại level hiện tại
     */
    public function approve(Contract $contract, User $approver, ?string $comments = null): void
    {
        if (!$contract->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Hợp đồng không ở trạng thái chờ phê duyệt.'
            ]);
        }

        $currentStep = $contract->getCurrentApprovalStep();

        if (!$currentStep) {
            throw ValidationException::withMessages([
                'approval' => 'Không tìm thấy bước phê duyệt hiện tại.'
            ]);
        }

        if ($currentStep->approver_id && $currentStep->approver_id != $approver->id) {
            throw ValidationException::withMessages([
                'approver' => 'Bạn không có quyền phê duyệt bước này.'
            ]);
        }

        DB::transaction(function () use ($contract, $currentStep, $approver, $comments) {
            // Cập nhật approval step hiện tại
            $currentStep->update([
                'status' => ApprovalStatus::APPROVED,
                'approver_id' => $approver->id,
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            // Kiểm tra xem có bước tiếp theo không
            $nextStep = $contract->getCurrentApprovalStep();

            // Kiểm tra overlap contracts trước khi activate (chỉ ở bước cuối)
            if (!$nextStep) {
                $this->checkContractOverlap($contract);
            }

            if (!$nextStep) {
                // Tất cả đã duyệt -> Active hợp đồng
                $contract->update([
                    'status' => ContractStatus::ACTIVE->value,
                    'approver_id' => $approver->id,
                    'approved_at' => now(),
                ]);

                activity('contract')
                    ->performedOn($contract)
                    ->causedBy($approver)
                    ->withProperties([
                        'contract_number' => $contract->contract_number,
                        'action' => 'fully_approved',
                        'level' => $currentStep->level->label(),
                    ])
                    ->log('Phê duyệt hoàn tất - Hợp đồng hiệu lực');

                // Dispatch event để gửi notification
                event(new ContractApproved($contract, $approver, $comments));
            } else {
                activity('contract')
                    ->performedOn($contract)
                    ->causedBy($approver)
                    ->withProperties([
                        'contract_number' => $contract->contract_number,
                        'action' => 'approved',
                        'level' => $currentStep->level->label(),
                        'next_level' => $nextStep->level->label(),
                    ])
                    ->log('Phê duyệt bước ' . $currentStep->level->label());

                // Dispatch event cho approval bước hiện tại
                event(new ContractApproved($contract, $approver, $comments));
            }
        });
    }

    /**
     * Từ chối hợp đồng
     */
    public function reject(Contract $contract, User $approver, string $comments): void
    {
        if (!$contract->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Hợp đồng không ở trạng thái chờ phê duyệt.'
            ]);
        }

        $currentStep = $contract->getCurrentApprovalStep();

        if (!$currentStep) {
            throw ValidationException::withMessages([
                'approval' => 'Không tìm thấy bước phê duyệt hiện tại.'
            ]);
        }

        if ($currentStep->approver_id && $currentStep->approver_id != $approver->id) {
            throw ValidationException::withMessages([
                'approver' => 'Bạn không có quyền từ chối phê duyệt bước này.'
            ]);
        }

        DB::transaction(function () use ($contract, $currentStep, $approver, $comments) {
            // Cập nhật approval step hiện tại
            $currentStep->update([
                'status' => ApprovalStatus::REJECTED,
                'approver_id' => $approver->id,
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            // Reject tất cả các bước còn lại
            $contract->approvals()
                ->where('status', ApprovalStatus::PENDING)
                ->update(['status' => ApprovalStatus::REJECTED]);

            // Cập nhật status hợp đồng về DRAFT
            $contract->update([
                'status' => ContractStatus::DRAFT->value,
                'approver_id' => $approver->id,
                'rejected_at' => now(),
                'approval_note' => $comments,
            ]);

            activity('contract')
                ->performedOn($contract)
                ->causedBy($approver)
                ->withProperties([
                    'contract_number' => $contract->contract_number,
                    'action' => 'rejected',
                    'level' => $currentStep->level->label(),
                    'comments' => $comments,
                ])
                ->log('Từ chối phê duyệt');

            // Dispatch event để gửi notification
            event(new ContractRejected($contract, $approver, $comments));
        });
    }

    /**
     * Thu hồi yêu cầu phê duyệt (chỉ người tạo hoặc admin)
     */
    public function recall(Contract $contract): void
    {
        if (!$contract->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể thu hồi hợp đồng đang chờ phê duyệt.'
            ]);
        }

        // Kiểm tra xem có bước nào đã duyệt chưa
        $hasApproved = $contract->approvals()->where('status', ApprovalStatus::APPROVED)->exists();

        if ($hasApproved) {
            throw ValidationException::withMessages([
                'approval' => 'Không thể thu hồi hợp đồng đã được phê duyệt một phần.'
            ]);
        }

        DB::transaction(function () use ($contract) {
            // Xóa tất cả approval steps
            $contract->approvals()->delete();

            // Đưa về trạng thái DRAFT
            $contract->update(['status' => ContractStatus::DRAFT->value]);

            activity('contract')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties([
                    'contract_number' => $contract->contract_number,
                    'action' => 'recalled',
                ])
                ->log('Thu hồi yêu cầu phê duyệt');
        });
    }

    /**
     * Kiểm tra user có quyền phê duyệt contract này không
     */
    public function canUserApprove(Contract $contract, User $user): bool
    {
        if (!$contract->isPendingApproval()) {
            return false;
        }

        $currentStep = $contract->getCurrentApprovalStep();

        if (!$currentStep) {
            return false;
        }

        // Nếu đã assign approver cụ thể, chỉ người đó mới duyệt được
        if ($currentStep->approver_id) {
            return $currentStep->approver_id == $user->id; // Loose comparison vì approver_id có thể là string
        }

        // Nếu chưa assign, kiểm tra theo role (chỉ Director)
        return match($currentStep->level) {
            ApprovalLevel::DIRECTOR => $user->hasRole('Director'),
            default => false,
        };
    }

    /**
     * Tìm Director phụ trách phê duyệt hợp đồng
     *
     * Quy trình: Tất cả hợp đồng (bất kể department nào) đều được duyệt bởi HEAD của phòng Nhân Sự
     *
     * Fallback chain:
     * 1. Tìm Director được assign cho phòng Nhân Sự (qua role_scopes)
     * 2. Nếu không có → Tìm HEAD của phòng Nhân Sự (qua employee_assignments)
     * 3. Nếu không có → Tìm User có role Director bất kỳ
     */
    protected function findDirectorForContract(Contract $contract): ?User
    {
        // Tìm department "Nhân Sự" hoặc "Hành chính Nhân sự"
        $hrDepartment = \App\Models\Department::whereIn('name', [
            'Phòng Nhân Sự',
        ])->first();

        if (!$hrDepartment) {
            // Fallback cuối: Lấy Director bất kỳ
            \Log::warning('Không tìm thấy phòng Nhân Sự, sử dụng Director mặc định');
            return User::role('Director')->first();
        }

        // Method 1: Tìm Director được assign cho HR department (qua role_scopes)
        $director = \App\Models\RoleScope::findUserWithRoleInDepartment('Director', $hrDepartment->id);

        if ($director) {
            \Log::info("Tìm thấy Director qua role_scopes", [
                'director_id' => $director->id,
                'director_email' => $director->email,
                'department' => $hrDepartment->name
            ]);
            return $director;
        }

        // Method 2: Tìm HEAD của HR department (qua employee_assignments)
        $headAssignment = \App\Models\EmployeeAssignment::where('department_id', $hrDepartment->id)
            ->where('role_type', 'HEAD')
            ->where('status', 'ACTIVE')
            ->first();

        if ($headAssignment && $headAssignment->employee) {
            $headUser = $headAssignment->employee->user;
            if ($headUser) {
                \Log::info("Tìm thấy HEAD của phòng Nhân Sự", [
                    'head_id' => $headUser->id,
                    'head_email' => $headUser->email,
                    'department' => $hrDepartment->name
                ]);
                return $headUser;
            }
        }

        // Fallback cuối: Lấy Director bất kỳ
        \Log::warning('Không tìm thấy Director/HEAD của phòng Nhân Sự, sử dụng Director mặc định');
        return User::role('Director')->first();
    }

    /**
     * Lấy danh sách contracts đang chờ user phê duyệt
     */
    public function getPendingContractsForUser(User $user)
    {
        // Chỉ Director mới có contracts chờ duyệt
        if (!$user->hasRole('Director')) {
            return collect();
        }

        $levels = [ApprovalLevel::DIRECTOR];

        return Contract::whereHas('approvals', function ($query) use ($user, $levels) {
            $query->where('status', ApprovalStatus::PENDING)
                ->whereIn('level', $levels)
                ->where(function ($q) use ($user) {
                    $q->where('approver_id', $user->id)
                      ->orWhereNull('approver_id');
                });
        })->with(['employee', 'department', 'position', 'approvals.approver'])
          ->get();
    }

    /**
     * Kiểm tra xem có hợp đồng nào đang active cho nhân viên này không
     * (Chỉ cho phép 1 hợp đồng active tại 1 thời điểm)
     */
    protected function checkContractOverlap(Contract $contract): void
    {
        $existingActive = Contract::where('employee_id', $contract->employee_id)
            ->where('id', '!=', $contract->id)
            ->where('status', ContractStatus::ACTIVE->value)
            ->where(function($q) use ($contract) {
                // Kiểm tra overlap dates
                $q->where(function($q2) use ($contract) {
                    // Contract mới bắt đầu trong khoảng contract cũ
                    $q2->where('start_date', '<=', $contract->start_date)
                       ->where(function($q3) use ($contract) {
                           $q3->whereNull('end_date')
                              ->orWhere('end_date', '>=', $contract->start_date);
                       });
                })->orWhere(function($q2) use ($contract) {
                    // Contract cũ bắt đầu trong khoảng contract mới
                    if ($contract->end_date) {
                        $q2->whereBetween('start_date', [$contract->start_date, $contract->end_date]);
                    }
                });
            })
            ->exists();

        if ($existingActive) {
            throw ValidationException::withMessages([
                'overlap' => 'Nhân viên đã có hợp đồng hiệu lực trong khoảng thời gian này. Vui lòng kết thúc hợp đồng cũ trước.'
            ]);
        }
    }
}
