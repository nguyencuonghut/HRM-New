<?php

namespace App\Events;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractTerminated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Contract $contract,
        public User $terminator,
        public string $reason,
        public ?string $note = null
    ) {
        //
    }
}
