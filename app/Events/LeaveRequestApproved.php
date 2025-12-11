<?php

namespace App\Events;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestApproved
{
    use Dispatchable, SerializesModels;

    public LeaveRequest $leaveRequest;
    public ?User $approver;

    /**
     * Create a new event instance.
     */
    public function __construct(LeaveRequest $leaveRequest, ?User $approver = null)
    {
        $this->leaveRequest = $leaveRequest;
        $this->approver = $approver;
    }
}
