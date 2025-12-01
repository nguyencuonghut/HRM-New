<?php

namespace App\Events;

use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppendixRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContractAppendix $appendix;
    public User $rejector;
    public ?string $comments;

    /**
     * Create a new event instance.
     */
    public function __construct(ContractAppendix $appendix, User $rejector, ?string $comments = null)
    {
        $this->appendix = $appendix;
        $this->rejector = $rejector;
        $this->comments = $comments;
    }
}
