<?php

namespace App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TicketReservationCancelled extends ShouldBeStored
{
    public function __construct(
        public string $ticketReservationUuid,
    )
    {
    }
}
