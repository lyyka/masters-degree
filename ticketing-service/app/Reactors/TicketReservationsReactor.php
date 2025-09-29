<?php

namespace App\Reactors;

use App\Events\TicketPurchased;
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
}
