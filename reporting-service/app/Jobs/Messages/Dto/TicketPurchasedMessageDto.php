<?php

namespace App\Jobs\Messages\Dto;

readonly class TicketPurchasedMessageDto
{
    public function __construct(
        private array $data
    )
    {
    }

    public function getTicketUuid(): string
    {
        return $this->data['ticket_uuid'];
    }

    public function getTicketReservationUuid(): string
    {
        return $this->data['ticket_reservation_uuid'];
    }

    public function getQuantity(): int
    {
        return $this->data['quantity'];
    }

    public function getUnitPrice(): int
    {
        return $this->data['unit_price'];
    }

    public function getHolderFirstName(): string
    {
        return $this->data['holder_first_name'];
    }

    public function getHolderLastName(): string
    {
        return $this->data['holder_last_name'];
    }

    public function getHolderEmail(): string
    {
        return $this->data['holder_email'];
    }
}
