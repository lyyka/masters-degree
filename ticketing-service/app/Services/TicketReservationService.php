<?php

namespace App\Services;

use App\Aggregates\TicketAggregate;
use App\Exceptions\TicketReservationAlreadyCancelled;
use App\Exceptions\TicketReservationAlreadyCheckedIn;
use App\Exceptions\TicketReservationCannotBeCheckedIn;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\UpdateHolderRequest;
use App\Jobs\Tickets\PurchaseTicketJob;
use App\Models\Ticket;
use App\Models\TicketReservation;

class TicketReservationService
{
    public function purchaseTicket(PurchaseRequest $request, Ticket $ticket): void
    {
        // `bought_qty` is updated when reservation is made so it's safe
        // to check here since it is consistent with the current state.
        if ($ticket->available_quantity - $ticket->bought_qty < 0) {
            return;
        }

        $reqId = \Str::uuid();
        \Cache::put($reqId, microtime(true));

        PurchaseTicketJob::dispatch(
            $ticket->uuid,
            $ticket->event_id,
            $request->integer('quantity'),
            $request->string('first_name'),
            $request->string('last_name'),
            $request->string('email'),
            $reqId
        )->onQueue('commands');

        //TicketAggregate::retrieve($ticket->uuid)->purchaseTicket(
        //    $request->integer('quantity'),
        //    $request->string('first_name'),
        //    $request->string('last_name'),
        //    $request->string('email'),
        //)->persist();
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

        TicketAggregate::retrieve($ticketReservation->event_id)
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

        TicketAggregate::retrieve($ticketReservation->event_id)
            ->cancelTicketReservation($ticketReservation->uuid)
            ->persist();
    }

    /**
     * @throws TicketReservationAlreadyCheckedIn
     * @throws TicketReservationAlreadyCancelled
     */
    public function updateTicketReservationHolder(UpdateHolderRequest $request, TicketReservation $ticketReservation): void
    {
        if ($ticketReservation->checked_in_at !== null) {
            throw new TicketReservationAlreadyCheckedIn();
        }

        if ($ticketReservation->cancelled_at !== null) {
            throw new TicketReservationAlreadyCancelled();
        }

        TicketAggregate::retrieve($ticketReservation->event_id)
            ->updateTicketReservationHolder(
                $ticketReservation->uuid,
                $request->string('first_name'),
                $request->string('last_name'),
                $request->string('email'),
            )
            ->persist();
    }
}
