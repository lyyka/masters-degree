<?php

namespace App\Jobs\Tickets;

use App\Aggregates\TicketAggregate;
use App\Exceptions\NotEnoughTicketsAvailable;
use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Log;

class PurchaseTicketJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ticketUuid,
        private readonly int    $quantity,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $email,
    )
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->ticketUuid)];
    }

    public function handle(): void
    {
        try {
            $start = microtime(true);
            TicketAggregate::retrieve($this->ticketUuid)->purchaseTicket(
                $this->quantity,
                $this->firstName,
                $this->lastName,
                $this->email,
            )->persist();
            $end = microtime(true);

            $cacheData = Cache::get('PurchaseTicketJob', ['total' => 0, 'count' => 0]);
            $cacheData['total'] += $end - $start;
            $cacheData['count'] += 1;
            Cache::put('PurchaseTicketJob', $cacheData);

            Log::info('Websocket communication about success here');
        } catch (NotEnoughTicketsAvailable) {
            Log::info('Websocket communication about error here');
        }
    }
}
