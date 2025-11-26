<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contract $contract;

    /**
     * Create a new event instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }
}
