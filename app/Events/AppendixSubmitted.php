<?php

namespace App\Events;

use App\Models\ContractAppendix;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppendixSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContractAppendix $appendix;

    /**
     * Create a new event instance.
     */
    public function __construct(ContractAppendix $appendix)
    {
        $this->appendix = $appendix;
    }
}
