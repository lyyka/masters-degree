<?php

namespace App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TicketReservationCheckedIn extends ShouldBeStored
{
    public function __construct(
        public string $ticketReservationUuid,
    )
    {
    }
}
