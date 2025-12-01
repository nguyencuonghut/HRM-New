<?php

namespace App\Events;

use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppendixApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContractAppendix $appendix;
    public User $approver;
    public ?string $comments;

    /**
     * Create a new event instance.
     */
    public function __construct(ContractAppendix $appendix, User $approver, ?string $comments = null)
    {
        $this->appendix = $appendix;
        $this->approver = $approver;
        $this->comments = $comments;
    }
}
