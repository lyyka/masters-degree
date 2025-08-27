<?php

namespace App\Projectors;

use App\Events\TicketPurchased;
use App\Events\TicketReservationCancelled;
use App\Events\TicketReservationCheckedIn;
use App\Events\TicketReservationHolderUpdated;
use App\Models\Ticket;
use App\Models\TicketReservation;
use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Throwable;

class TicketReservationsProjector extends Projector implements ShouldQueue
{
    /**
     * @throws Throwable
     */
    public function onTicketPurchased(TicketPurchased $event): void
    {
        $start = microtime(true);
        $ticket = Ticket::where('uuid', $event->ticketUuid)->firstOrFail();
        DB::transaction(function () use ($event, $ticket) {
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
                'unit_price' => $event->unitPrice,
                'created_at' => $event->createdAt(),
                'updated_at' => $event->createdAt(),
            ])->writeable()->save();

            $ticket->increment('bought_qty', $event->quantity);
        });
        $end = microtime(true);

        $cacheData = Cache::get('onTicketPurchased', ['total' => 0, 'count' => 0]);
        $cacheData['total'] += $end - $start;
        $cacheData['count'] += 1;
        Cache::put('onTicketPurchased', $cacheData);

        $waitTime = $end - Cache::pull($event->reqId, $end);
        $currentTime = Cache::get('largestWaitTime', 0);
        if ($waitTime > $currentTime) {
            Cache::put('largestWaitTime', $waitTime);
        }
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
