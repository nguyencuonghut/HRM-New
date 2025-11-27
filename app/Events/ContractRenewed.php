<?php

namespace App\Events;

use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractRenewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContractAppendix $appendix;
    public User $creator;

    /**
     * Create a new event instance.
     */
    public function __construct(ContractAppendix $appendix, User $creator)
    {
        $this->appendix = $appendix;
        $this->creator = $creator;
    }
}
