<?php

namespace App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TicketPurchased extends ShouldBeStored
{
    public function __construct(
        public string $ticketUuid,
        public string $ticketReservationUuid,
        public int    $quantity,
        public int    $unitPrice,
        public string $holderFirstName,
        public string $holderLastName,
        public string $holderEmail,
        public string $reqId,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'ticket_uuid' => $this->ticketUuid,
            'ticket_reservation_uuid' => $this->ticketReservationUuid,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'holder_first_name' => $this->holderFirstName,
            'holder_last_name' => $this->holderLastName,
            'holder_email' => $this->holderEmail,
        ];
    }
}
