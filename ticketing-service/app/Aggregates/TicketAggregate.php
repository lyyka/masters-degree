<?php

namespace App\Aggregates;

use App\Events\TicketPurchased;
use App\Events\TicketReservationCancelled;
use App\Events\TicketReservationCheckedIn;
use App\Events\TicketReservationHolderUpdated;
use App\Exceptions\NotEnoughTicketsAvailable;
use App\Models\Ticket;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Str;

class TicketAggregate extends AggregateRoot
{
    private int $ticketsSold = 0;

    public function applyTicketPurchased(TicketPurchased $event): void
    {
        $this->ticketsSold += $event->quantity;
    }

    /**
     * @throws NotEnoughTicketsAvailable
     */
    public function purchaseTicket(int $quantity, string $holderFirstName, string $holderLastName, string $holderEmail): self
    {
        $ticket = Ticket::where('uuid', $this->uuid())->firstOrFail();

        if ($ticket->available_quantity - $this->ticketsSold - $quantity < 0) {
            throw new NotEnoughTicketsAvailable();
        }

        $this->recordThat(new TicketPurchased($this->uuid(), Str::uuid(), $quantity, $ticket->price, $holderFirstName, $holderLastName, $holderEmail));

        return $this;
    }

    public function checkInTicketReservation(string $ticketReservationUuid): self
    {
        $this->recordThat(new TicketReservationCheckedIn($ticketReservationUuid));

        return $this;
    }

    public function cancelTicketReservation(string $ticketReservationUuid): self
    {
        $this->recordThat(new TicketReservationCancelled($ticketReservationUuid));

        return $this;
    }

    public function updateTicketReservationHolder(string $ticketReservationUuid, string $holderFirstName, string $holderLastName, string $holderEmail): self
    {
        $this->recordThat(new TicketReservationHolderUpdated($ticketReservationUuid, $holderFirstName, $holderLastName, $holderEmail));

        return $this;
    }
}
