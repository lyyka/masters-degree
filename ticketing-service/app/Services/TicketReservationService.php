<?php

namespace App\Services;

use App\Aggregates\TicketAggregate;
use App\Exceptions\NotEnoughTicketsAvailable;
use App\Exceptions\TicketReservationAlreadyCancelled;
use App\Exceptions\TicketReservationAlreadyCheckedIn;
use App\Exceptions\TicketReservationCannotBeCheckedIn;
use App\Http\Requests\PurchaseRequest;
use App\Models\Ticket;
use App\Models\TicketReservation;

class TicketReservationService
{
    /**
     * @throws NotEnoughTicketsAvailable
     */
    public function purchaseTicket(PurchaseRequest $request, Ticket $ticket): void
    {
        TicketAggregate::retrieve($ticket->uuid)->purchaseTicket(
            $request->integer('quantity'),
            $request->string('first_name'),
            $request->string('last_name'),
            $request->string('email'),
        )->persist();
    }

    /**
     * @throws TicketReservationAlreadyCheckedIn
     * @throws TicketReservationCannotBeCheckedIn
     */
    public function checkInTicketReservation(TicketReservation $ticketReservation): void
    {
        if ($ticketReservation->checked_in_at !== null) {
            throw new TicketReservationAlreadyCheckedIn();
        }

        if ($ticketReservation->cancelled_at !== null) {
            throw new TicketReservationCannotBeCheckedIn();
        }

        TicketAggregate::retrieve($ticketReservation->ticket_uuid)
            ->checkInTicketReservation($ticketReservation->uuid)
            ->persist();
    }

    /**
     * @throws TicketReservationAlreadyCancelled
     */
    public function cancelTicketReservation(TicketReservation $ticketReservation): void
    {
        if ($ticketReservation->cancelled_at !== null) {
            throw new TicketReservationAlreadyCancelled();
        }

        TicketAggregate::retrieve($ticketReservation->ticket_uuid)
            ->cancelTicketReservation($ticketReservation->uuid)
            ->persist();
    }
}
