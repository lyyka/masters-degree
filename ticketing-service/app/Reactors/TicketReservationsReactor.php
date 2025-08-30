<?php

namespace App\Reactors;

use App\Events\TicketPurchased;
use App\Events\TicketReservationCancelled;
use App\Events\TicketReservationCheckedIn;
use App\Events\TicketReservationHolderUpdated;
use App\Jobs\Messages\TicketReservationCancelledMessage;
use App\Jobs\Messages\TicketReservationCheckedInMessage;
use App\Jobs\Messages\TicketReservationHolderUpdatedMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

/**
 * This should push messages to our message broker to notify the reporting service.
 */
class TicketReservationsReactor extends Reactor implements ShouldQueue
{
    public function onTicketPurchased(TicketPurchased $event): void
    {
        //TicketPurchasedMessage::dispatch($event->toArray())->onQueue('ticket-events-outgoing');
    }

    public function onTicketReservationCheckedIn(TicketReservationCheckedIn $event): void
    {
        TicketReservationCheckedInMessage::dispatch($event->toArray())->onQueue('ticket-events-outgoing');
    }

    public function onTicketReservationCancelled(TicketReservationCancelled $event): void
    {
        TicketReservationCancelledMessage::dispatch($event->toArray())->onQueue('ticket-events-outgoing');
    }

    public function onTicketReservationHolderUpdate(TicketReservationHolderUpdated $event): void
    {
        TicketReservationHolderUpdatedMessage::dispatch($event->toArray())->onQueue('ticket-events-outgoing');
    }
}
