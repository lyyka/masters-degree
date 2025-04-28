<?php

namespace App\Jobs\Messages;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TicketReservationHolderUpdatedMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly array $data)
    {
    }
}
