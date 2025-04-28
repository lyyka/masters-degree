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

    public function toArray(): array
    {
        return [
            'ticket_reservation_uuid' => $this->ticketReservationUuid,
        ];
    }
}
