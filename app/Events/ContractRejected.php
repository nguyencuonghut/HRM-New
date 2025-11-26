<?php

namespace App\Events;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contract $contract;
    public User $rejector;
    public ?string $comments;

    /**
     * Create a new event instance.
     */
    public function __construct(Contract $contract, User $rejector, ?string $comments = null)
    {
        $this->contract = $contract;
        $this->rejector = $rejector;
        $this->comments = $comments;
    }
}
