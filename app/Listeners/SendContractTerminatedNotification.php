<?php

namespace App\Listeners;

use App\Events\ContractTerminated;
use App\Notifications\ContractTerminatedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(ContractTerminated::class)]
class SendContractTerminatedNotification implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    /**
     * The unique ID of the listener.
     */
    public function uniqueId(): string
    {
        return static::class . '-' . ($this->event->contract->id ?? 'unknown');
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 60;

    /**
     * Tìm Director hiện tại có quyền approve hợp đồng
     * (Sử dụng logic từ ContractApprovalService)
     */
    protected function findCurrentDirector($contract, $approvalService): ?User
    {
        // Sử dụng reflection để gọi protected method findDirectorForContract
        $reflection = new \ReflectionClass($approvalService);
        $method = $reflection->getMethod('findDirectorForContract');
        $method->setAccessible(true);

        return $method->invoke($approvalService, $contract);
    }

    /**
     * Handle the event.
     */
    public function handle(ContractTerminated $event): void
    {
        $contract = $event->contract;
        $terminator = $event->terminator;
        $reason = $event->reason;
        $note = $event->note;

        $sentEmails = []; // Track để tránh gửi trùng

        // 1. Gửi email trực tiếp cho nhân viên (personal_email và company_email)
        if ($contract->employee) {
            $employee = $contract->employee;
            $employeeNotification = new ContractTerminatedNotification($contract, $terminator, $reason, $note, 'employee');

            // Gửi tới personal_email
            if ($employee->personal_email && !in_array($employee->personal_email, $sentEmails)) {
                \Illuminate\Support\Facades\Notification::route('mail', $employee->personal_email)
                    ->notify($employeeNotification);
                $sentEmails[] = $employee->personal_email;
            }

            // Gửi tới company_email
            if ($employee->company_email && !in_array($employee->company_email, $sentEmails)) {
                \Illuminate\Support\Facades\Notification::route('mail', $employee->company_email)
                    ->notify($employeeNotification);
                $sentEmails[] = $employee->company_email;
            }
        }

        // 2. Gửi notification cho người tạo hợp đồng (qua tài khoản user)
        if ($contract->created_by_user) {
            $creatorEmail = $contract->created_by_user->email;
            if (!in_array($creatorEmail, $sentEmails)) {
                $managerNotification = new ContractTerminatedNotification($contract, $terminator, $reason, $note, 'manager');
                $contract->created_by_user->notify($managerNotification);
                $sentEmails[] = $creatorEmail;
            }
        }

        // 3. Gửi notification cho Director hiện tại (người có quyền approve)
        // Không dùng contract.approver cũ vì có thể đã thay đổi
        $approvalService = app(\App\Services\ContractApprovalService::class);
        $currentDirector = $this->findCurrentDirector($contract, $approvalService);

        if ($currentDirector) {
            $directorEmail = $currentDirector->email;
            if (!in_array($directorEmail, $sentEmails)) {
                $managerNotification = new ContractTerminatedNotification($contract, $terminator, $reason, $note, 'manager');
                $currentDirector->notify($managerNotification);
                $sentEmails[] = $directorEmail;
            }
        }

        // 4. Gửi cho Head của phòng/ban có nhân sự nghỉ việc
        // Bao gồm HEAD của department hiện tại và tất cả department cha (nếu có)
        if ($contract->department_id) {
            $department = \App\Models\Department::find($contract->department_id);

            if ($department) {
                $departmentIds = [$department->id];

                // Nếu department có parent, thu thập tất cả parent IDs
                if ($department->parent_id) {
                    $currentDept = $department;
                    while ($currentDept->parent_id) {
                        $parentDept = \App\Models\Department::find($currentDept->parent_id);
                        if ($parentDept) {
                            $departmentIds[] = $parentDept->id;
                            $currentDept = $parentDept;
                        } else {
                            break;
                        }
                    }
                }

                // Tìm tất cả HEAD của các department (hiện tại + cha)
                $headAssignments = \App\Models\EmployeeAssignment::whereIn('department_id', $departmentIds)
                    ->where('role_type', 'HEAD')
                    ->where('status', 'ACTIVE')
                    ->whereDate('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>=', now());
                    })
                    ->with('employee')
                    ->get();

                foreach ($headAssignments as $headAssignment) {
                    if ($headAssignment->employee) {
                        $headEmployee = $headAssignment->employee;
                        // Truyền tên HEAD để hiển thị đúng trong email
                        $headNotification = new ContractTerminatedNotification($contract, $terminator, $reason, $note, 'head', $headEmployee->full_name);

                        // Gửi tới company_email của Head
                        if ($headEmployee->company_email && !in_array($headEmployee->company_email, $sentEmails)) {
                            \Illuminate\Support\Facades\Notification::route('mail', $headEmployee->company_email)
                                ->notify($headNotification);
                            $sentEmails[] = $headEmployee->company_email;
                        }

                        // Nếu Head có tài khoản user, gửi luôn notification vào hệ thống
                        if ($headEmployee->user) {
                            $headUserEmail = $headEmployee->user->email;
                            if (!in_array($headUserEmail, $sentEmails)) {
                                $headEmployee->user->notify($headNotification);
                                $sentEmails[] = $headUserEmail;
                            }
                        }
                    }
                }
            }
        }
    }
}
