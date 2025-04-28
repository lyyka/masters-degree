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

    public function toArray(): array
    {
        return [
            'ticket_reservation_uuid' => $this->ticketReservationUuid,
            'holder_first_name' => $this->holderFirstName,
            'holder_last_name' => $this->holderLastName,
            'holder_email' => $this->holderEmail,
        ];
    }
}
