<?php

namespace App\Jobs\Messages\Dto;

readonly class TicketReservationChangeDto
{
    public function __construct(
        private array $data
    )
    {
    }

    public function getTicketReservationUuid(): string
    {
        return $this->data['ticket_reservation_uuid'];
    }
}
