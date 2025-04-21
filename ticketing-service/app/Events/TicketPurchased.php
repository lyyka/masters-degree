<?php

namespace App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TicketPurchased extends ShouldBeStored
{
    public function __construct(
        public string $ticketUuid,
        public string $ticketReservationUuid,
        public int    $quantity,
        public string $holderFirstName,
        public string $holderLastName,
        public string $holderEmail,
    )
    {
    }
}
