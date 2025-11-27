<?php

namespace App\Listeners;

use App\Events\ContractRenewed;
use App\Notifications\ContractRenewedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendContractRenewedNotification implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->event->appendix->id . '_renewed';
    }

    /**
     * The event instance.
     */
    public function __construct(
        #[ListensTo(ContractRenewed::class)]
        public ContractRenewed $event
    ) {}

    /**
     * Handle the event.
     */
    public function handle(): void
    {
        $appendix = $this->event->appendix;
        $contract = $appendix->contract;
        $creator = $this->event->creator;
        $employee = $contract->employee;

        $sentEmails = [];

        // 1. Gửi cho nhân viên (personal_email + company_email)
        $employeeNotification = new ContractRenewedNotification($appendix, $creator, 'employee');

        if ($employee->personal_email && !in_array($employee->personal_email, $sentEmails)) {
            Notification::route('mail', $employee->personal_email)->notify($employeeNotification);
            $sentEmails[] = $employee->personal_email;
        }

        if ($employee->company_email && !in_array($employee->company_email, $sentEmails)) {
            Notification::route('mail', $employee->company_email)->notify($employeeNotification);
            $sentEmails[] = $employee->company_email;
        }

        // Nếu employee có user account, gửi database notification
        if ($employee->user) {
            $employee->user->notify($employeeNotification);
        }

        // 2. Gửi cho người tạo hợp đồng (nếu có created_by)
        if ($contract->created_by && $contract->created_by !== $creator->id) {
            $contractCreator = \App\Models\User::find($contract->created_by);
            if ($contractCreator) {
                $managerNotification = new ContractRenewedNotification($appendix, $creator, 'manager');
                $creatorEmail = $contractCreator->email;
                if (!in_array($creatorEmail, $sentEmails)) {
                    $contractCreator->notify($managerNotification);
                    $sentEmails[] = $creatorEmail;
                }
            }
        }

        // 3. Gửi cho Giám đốc phê duyệt hiện tại (approver_id của contract)
        if ($contract->approver_id) {
            $currentDirector = \App\Models\User::find($contract->approver_id);
            if ($currentDirector) {
                $managerNotification = new ContractRenewedNotification($appendix, $creator, 'manager');
                $directorEmail = $currentDirector->email;
                if (!in_array($directorEmail, $sentEmails)) {
                    $currentDirector->notify($managerNotification);
                    $sentEmails[] = $directorEmail;
                }
            }
        }

        // 4. Gửi cho Head của phòng/ban
        if ($contract->department_id) {
            $department = \App\Models\Department::find($contract->department_id);

            if ($department) {
                $departmentIds = [$department->id];

                // Thu thập tất cả parent IDs
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

                // Tìm tất cả HEAD
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
                        $headNotification = new ContractRenewedNotification($appendix, $creator, 'head', $headEmployee->full_name);

                        // Gửi tới company_email
                        if ($headEmployee->company_email && !in_array($headEmployee->company_email, $sentEmails)) {
                            Notification::route('mail', $headEmployee->company_email)->notify($headNotification);
                            $sentEmails[] = $headEmployee->company_email;
                        }

                        // Nếu có user account
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
