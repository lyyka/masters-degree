<?php

namespace App\Projectors;

use App\Events\TicketPurchased;
use App\Events\TicketReservationCancelled;
use App\Events\TicketReservationCheckedIn;
use App\Events\TicketReservationHolderUpdated;
use App\Models\Ticket;
use App\Models\TicketReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TicketReservationsProjector extends Projector implements ShouldQueue
{
    public function onTicketPurchased(TicketPurchased $event): void
    {
        $ticket = Ticket::where('uuid', $event->ticketUuid)->firstOrFail();

        $ticketReservation = new TicketReservation();
        $ticketReservation->fill([
            'uuid' => $event->ticketReservationUuid,
            'event_id' => $ticket->event_id,
            'ticket_id' => $ticket->id,
            'ticket_uuid' => $ticket->uuid,
            'holder_first_name' => $event->holderFirstName,
            'holder_last_name' => $event->holderLastName,
            'holder_email' => $event->holderEmail,
            'quantity' => $event->quantity,
            'created_at' => $event->createdAt(),
            'updated_at' => $event->createdAt(),
        ])->writeable()->save();
    }

    public function onTicketReservationCheckedIn(TicketReservationCheckedIn $event): void
    {
        TicketReservation::where('uuid', $event->ticketReservationUuid)->first()->fill([
            'checked_in_at' => $event->createdAt(),
            'updated_at' => $event->createdAt(),
        ])->writeable()->save();
    }

    public function onTicketReservationCancelled(TicketReservationCancelled $event): void
    {
        TicketReservation::where('uuid', $event->ticketReservationUuid)->first()->fill([
            'cancelled_at' => $event->createdAt(),
            'updated_at' => $event->createdAt(),
        ])->writeable()->save();
    }

    public function onTicketReservationHolderUpdated(TicketReservationHolderUpdated $event): void
    {
        TicketReservation::where('uuid', $event->ticketReservationUuid)->first()->fill([
            'holder_email' => $event->holderEmail,
            'holder_first_name' => $event->holderFirstName,
            'holder_last_name' => $event->holderLastName,
            'updated_at' => $event->createdAt(),
        ])->writeable()->save();
    }

    public function resetState(?string $aggregateUuid = null): void
    {
        if ($aggregateUuid !== null) {
            TicketReservation::where('ticket_uuid', $aggregateUuid)->delete();
        } else {
            TicketReservation::truncate();
        }
    }
}
