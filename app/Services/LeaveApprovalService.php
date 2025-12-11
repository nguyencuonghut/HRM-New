<?php

namespace App\Services;

use App\Events\LeaveRequestApproved;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveApprovalService
{
    /**
     * Get approval chain for a leave request based on employee's PRIMARY assignment
     *
     * Returns array of approver steps:
     * [
     *   ['step' => 1, 'approver_id' => uuid, 'approver_role' => 'LINE_MANAGER'],
     *   ['step' => 2, 'approver_id' => uuid, 'approver_role' => 'DIRECTOR'],
     *   ['step' => 3, 'approver_id' => uuid, 'approver_role' => 'HR'],
     * ]
     *
     * Logic:
     * 1. Find LINE_MANAGER: Get HEAD (role_type=HEAD) of employee's department from PRIMARY assignment
     * 2. Find DIRECTOR: Get HEAD of parent department (if exists)
     * 3. HR: Always required for final approval (HR Head user)
     */
    public function getApprovalChain(Employee $employee): array
    {
        $chain = [];
        $step = 1;

        // Get employee's PRIMARY assignment
        $primaryAssignment = EmployeeAssignment::where('employee_id', $employee->id)
            ->where('is_primary', true)
            ->where('status', 'ACTIVE')
            ->with(['department'])
            ->first();

        if (!$primaryAssignment) {
            Log::warning("No PRIMARY assignment found for employee {$employee->id}");
            return $chain;
        }

        // Step 1: LINE_MANAGER (Department HEAD)
        $lineManager = $this->findLineManager($employee, $primaryAssignment);
        if ($lineManager && $lineManager->employee && $lineManager->employee->user) {
            $chain[] = [
                'step' => $step++,
                'approver_id' => $lineManager->employee->user->id,
                'approver_role' => LeaveApproval::ROLE_LINE_MANAGER,
            ];
        }

        // Step 2: DIRECTOR (Root department HEAD) - only if there is a parent department
        if ($primaryAssignment->department && $primaryAssignment->department->parent_id) {
            $rootDepartmentId = $this->findRootDepartment($primaryAssignment->department);
            if ($rootDepartmentId) {
                $director = $this->findDirector($rootDepartmentId, $employee->id);
                if ($director && $director->employee && $director->employee->user) {
                    // Only add if not already in chain (avoid duplicate approver)
                    $directorUserId = $director->employee->user->id;
                    $alreadyInChain = collect($chain)->pluck('approver_id')->contains($directorUserId);

                    if (!$alreadyInChain) {
                        $chain[] = [
                            'step' => $step++,
                            'approver_id' => $directorUserId,
                            'approver_role' => LeaveApproval::ROLE_DIRECTOR,
                        ];
                    }
                }
            }
        }

        // Step 3: HR Head (final approval)
        $hrHead = $this->findHRHead();
        if ($hrHead) {
            // Only add if not already in chain and not the employee themselves
            $alreadyInChain = collect($chain)->pluck('approver_id')->contains($hrHead->id);

            if (!$alreadyInChain && $hrHead->id !== $employee->user_id) {
                $chain[] = [
                    'step' => $step++,
                    'approver_id' => $hrHead->id,
                    'approver_role' => LeaveApproval::ROLE_HR,
                ];
            }
        }

        return $chain;
    }

    /**
     * Find LINE_MANAGER: HEAD of employee's department or nearest parent department with HEAD (excluding self)
     */
    protected function findLineManager(Employee $employee, EmployeeAssignment $primaryAssignment): ?EmployeeAssignment
    {
        $currentDepartment = $primaryAssignment->department;

        // Traverse up the tree to find the first department with a HEAD
        while ($currentDepartment) {
            $head = EmployeeAssignment::where('department_id', $currentDepartment->id)
                ->where('role_type', 'HEAD')
                ->where('status', 'ACTIVE')
                ->where('employee_id', '!=', $employee->id) // Don't approve yourself
                ->whereHas('employee.user') // Ensure employee has user account
                ->with(['employee.user'])
                ->first();

            if ($head) {
                return $head;
            }

            // Move to parent department
            if ($currentDepartment->parent_id) {
                $currentDepartment = \App\Models\Department::find($currentDepartment->parent_id);
            } else {
                break;
            }
        }

        return null;
    }

    /**
     * Find root department (topmost parent) in the organization tree
     */
    protected function findRootDepartment($department): ?string
    {
        $current = $department;

        // Traverse up the tree until we find the root (no parent)
        while ($current && $current->parent_id) {
            $current = \App\Models\Department::find($current->parent_id);
        }

        return $current ? $current->id : null;
    }

    /**
     * Find DIRECTOR: HEAD of specified department (typically root department)
     */
    protected function findDirector(string $departmentId, int $excludeEmployeeId): ?EmployeeAssignment
    {
        return EmployeeAssignment::where('department_id', $departmentId)
            ->where('role_type', 'HEAD')
            ->where('status', 'ACTIVE')
            ->where('employee_id', '!=', $excludeEmployeeId) // Don't approve yourself
            ->whereHas('employee.user')
            ->with(['employee.user'])
            ->first();
    }

    /**
     * Find HR Head user (final approver)
     */
    protected function findHRHead(): ?User
    {
        // Find user with 'HR Head' role
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'HR Head');
        })->first();
    }

    /**
     * Check if user can auto-approve leave requests (Admin or Super Admin roles)
     */
    protected function canAutoApproveAsAdmin(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Super Admin']);
    }

    /**
     * Create approval chain for a new leave request
     *
     * @param LeaveRequest $leaveRequest
     * @return void
     */
    public function createApprovalChain(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        $chain = $this->getApprovalChain($employee);

        if (empty($chain)) {
            Log::warning("No approval chain generated for leave request {$leaveRequest->id}");
            return;
        }

        // Create approval records
        foreach ($chain as $approval) {
            LeaveApproval::create([
                'leave_request_id' => $leaveRequest->id,
                'approver_id' => $approval['approver_id'],
                'step' => $approval['step'],
                'approver_role' => $approval['approver_role'],
                'status' => LeaveApproval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Process approval/rejection of a leave request
     *
     * @param LeaveRequest $leaveRequest
     * @param User $approver Current user (approver)
     * @param string $action 'approve' or 'reject'
     * @param string|null $comment Optional comment
     * @return array ['success' => bool, 'message' => string]
     */
    public function processApproval(
        LeaveRequest $leaveRequest,
        User $approver,
        string $action,
        ?string $comment = null
    ): array {
        // Validate action
        if (!in_array($action, ['approve', 'reject'])) {
            return ['success' => false, 'message' => 'Invalid action'];
        }

        // Check if request is pending
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return ['success' => false, 'message' => 'Leave request is not pending'];
        }

        // Find approver's pending approval
        $approval = LeaveApproval::where('leave_request_id', $leaveRequest->id)
            ->where('approver_id', $approver->id)
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->first();

        if (!$approval) {
            return ['success' => false, 'message' => 'You are not authorized to approve this request'];
        }

        // Check if it's the next step in sequence
        $currentMaxStep = LeaveApproval::where('leave_request_id', $leaveRequest->id)
            ->where('status', LeaveApproval::STATUS_APPROVED)
            ->max('step') ?? 0;

        if ($approval->step !== $currentMaxStep + 1) {
            return ['success' => false, 'message' => 'Previous approval step is not completed yet'];
        }

        DB::beginTransaction();
        try {
            if ($action === 'approve') {
                // Update approval record
                $approval->update([
                    'status' => LeaveApproval::STATUS_APPROVED,
                    'comment' => $comment,
                    'approved_at' => now(),
                ]);

                // Auto-approve subsequent steps if same approver
                $this->autoApproveSubsequentSteps($leaveRequest, $approver->id, $approval->step, $comment);

                // Check if this is the final approval
                if ($leaveRequest->isFullyApproved()) {
                    $this->finalizeApproval($leaveRequest, $approver);
                }

                $message = 'Leave request approved successfully';
            } else {
                // Reject
                $approval->update([
                    'status' => LeaveApproval::STATUS_REJECTED,
                    'comment' => $comment,
                    'rejected_at' => now(),
                ]);

                // Reject the entire request
                $leaveRequest->update([
                    'status' => LeaveRequest::STATUS_REJECTED,
                ]);

                $message = 'Leave request rejected';
            }

            // Log activity
            activity()
                ->useLog('leave-approval')
                ->performedOn($leaveRequest)
                ->causedBy($approver)
                ->withProperties([
                    'action' => $action,
                    'step' => $approval->step,
                    'role' => $approval->approver_role,
                    'comment' => $comment,
                ])
                ->log($action === 'approve' ? 'Phê duyệt đơn nghỉ phép' : 'Từ chối đơn nghỉ phép');

            DB::commit();
            return ['success' => true, 'message' => $message];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing leave approval: {$e->getMessage()}");
            return ['success' => false, 'message' => 'An error occurred while processing approval'];
        }
    }

    /**
     * Auto-approve subsequent steps if they have the same approver
     */
    protected function autoApproveSubsequentSteps(LeaveRequest $leaveRequest, int $approverId, int $currentStep, ?string $comment): void
    {
        // Get all subsequent pending approvals for the same approver
        $subsequentApprovals = LeaveApproval::where('leave_request_id', $leaveRequest->id)
            ->where('approver_id', $approverId)
            ->where('step', '>', $currentStep)
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->orderBy('step')
            ->get();

        foreach ($subsequentApprovals as $approval) {
            $approval->update([
                'status' => LeaveApproval::STATUS_APPROVED,
                'comment' => $comment ?: 'Auto-approved (same approver)',
                'approved_at' => now(),
            ]);

            Log::info("Auto-approved step {$approval->step} for leave request {$leaveRequest->id}");
        }
    }

    /**
     * Finalize approval - mark request as APPROVED and deduct leave balance
     */
    protected function finalizeApproval(LeaveRequest $leaveRequest, ?User $approver = null): void
    {
        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // Dispatch event for insurance tracking
        event(new LeaveRequestApproved($leaveRequest, $approver));

        // Deduct from leave balance
        $year = Carbon::parse($leaveRequest->start_date)->year;
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            $balance->deductDays($leaveRequest->days);
        } else {
            // Create balance if not exists (should normally be initialized at year start)
            Log::warning("Leave balance not found for employee {$leaveRequest->employee_id}, creating new one");
            LeaveBalance::create([
                'employee_id' => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year' => $year,
                'total_days' => $leaveRequest->leaveType->days_per_year,
                'used_days' => $leaveRequest->days,
                'remaining_days' => $leaveRequest->leaveType->days_per_year - $leaveRequest->days,
                'carried_forward' => 0,
            ]);
        }
    }

    /**
     * Auto-approve leave request (for types that don't require approval)
     */
    public function autoApprove(LeaveRequest $leaveRequest): void
    {
        DB::beginTransaction();
        try {
            // Set submitted_at if not already set
            if (!$leaveRequest->submitted_at) {
                $leaveRequest->update(['submitted_at' => now()]);
            }

            // Finalize approval (will update status, dispatch event, and deduct balance)
            // Approver is current user (Admin/HR) or system
            $this->finalizeApproval($leaveRequest, auth()->user());

            // Log activity
            activity()
                ->useLog('leave-request')
                ->performedOn($leaveRequest)
                ->withProperties(['auto_approved' => true])
                ->log('Tự động phê duyệt đơn nghỉ phép');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error auto-approving leave request: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Submit leave request for approval
     */
    public function submitForApproval(LeaveRequest $leaveRequest, ?User $creator = null): array
    {
        // Validate request
        if ($leaveRequest->status !== LeaveRequest::STATUS_DRAFT) {
            return ['success' => false, 'message' => 'Leave request is not in DRAFT status'];
        }

        // Check if creator is Admin or HR - auto-approve immediately (real-world workflow)
        $currentUser = $creator ?? auth()->user();
        if ($currentUser && $this->canAutoApproveAsAdmin($currentUser)) {
            $this->autoApprove($leaveRequest);
            return ['success' => true, 'message' => 'Leave request approved (created by HR/Admin)'];
        }

        // Check if leave type requires approval
        if (!$leaveRequest->leaveType->requires_approval) {
            // Auto-approve
            $this->autoApprove($leaveRequest);
            return ['success' => true, 'message' => 'Leave request auto-approved'];
        }

        // Check leave balance (warning only, not blocking)
        $remainingDays = $leaveRequest->getRemainingDays();
        $exceedsDays = $leaveRequest->days - $remainingDays;
        if ($exceedsDays > 0) {
            // Log for tracking but don't block submission
            Log::warning("Leave request exceeds balance", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'requested_days' => $leaveRequest->days,
                'remaining_days' => $remainingDays,
                'exceeds_by' => $exceedsDays,
            ]);

            // Add note to request for approvers to see
            $leaveRequest->update([
                'note' => ($leaveRequest->note ? $leaveRequest->note . "\n\n" : '')
                    . "[HỆ THỐNG] Đơn này vượt {$exceedsDays} ngày phép. Những ngày vượt sẽ bị trừ vào công/lương."
            ]);
        }

        // Check for overlapping leave requests before submitting
        if ($overlapping = $leaveRequest->hasOverlappingLeave()) {
            $statusLabels = [
                LeaveRequest::STATUS_DRAFT => 'Nháp',
                LeaveRequest::STATUS_PENDING => 'Chờ duyệt',
                LeaveRequest::STATUS_APPROVED => 'Đã duyệt',
            ];
            $statusText = $statusLabels[$overlapping->status] ?? $overlapping->status;

            return [
                'success' => false,
                'message' => "Khoảng thời gian này đã có đơn nghỉ phép ({$statusText}). Vui lòng chọn ngày khác."
            ];
        }

        DB::beginTransaction();
        try {
            // Get approval chain first to check if there are any approvers
            $employee = $leaveRequest->employee;
            $chain = $this->getApprovalChain($employee);

            // If no approvers (e.g., HR Head approving their own leave), auto-approve
            if (empty($chain)) {
                $this->autoApprove($leaveRequest);
                DB::commit();
                return ['success' => true, 'message' => 'Leave request auto-approved (no approvers required)'];
            }

            // Update status to PENDING
            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            // Create approval chain
            $this->createApprovalChain($leaveRequest);

            // Log activity
            activity()
                ->useLog('leave-request')
                ->performedOn($leaveRequest)
                ->log('Nộp đơn nghỉ phép chờ phê duyệt');

            DB::commit();
            return ['success' => true, 'message' => 'Leave request submitted for approval'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error submitting leave request: {$e->getMessage()}");
            return ['success' => false, 'message' => 'An error occurred while submitting request'];
        }
    }

    /**
     * Get pending leave requests for an approver
     */
    public function getPendingRequestsForApprover(User $approver): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveRequest::pending()
            ->whereHas('approvals', function ($query) use ($approver) {
                $query->where('approver_id', $approver->id)
                    ->where('status', LeaveApproval::STATUS_PENDING);
            })
            ->with(['employee', 'leaveType', 'approvals' => function ($query) {
                $query->orderBy('step');
            }])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Get count of pending requests for an approver (for badge)
     */
    public function getPendingCountForApprover(User $approver): int
    {
        return LeaveRequest::pending()
            ->whereHas('approvals', function ($query) use ($approver) {
                $query->where('approver_id', $approver->id)
                    ->where('status', LeaveApproval::STATUS_PENDING);
            })
            ->count();
    }
}
