<?php

namespace App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TicketReservationHolderUpdated extends ShouldBeStored
{
    public function __construct(
        public string $ticketReservationUuid,
        public string $holderFirstName,
        public string $holderLastName,
        public string $holderEmail,
    )
    {
    }
}
