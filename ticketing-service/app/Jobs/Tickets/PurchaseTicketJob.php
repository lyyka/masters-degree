<?php

namespace App\Jobs\Tickets;

use App\Aggregates\TicketAggregate;
use App\Exceptions\NotEnoughTicketsAvailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class PurchaseTicketJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ticketUuid,
        private readonly int    $quantity,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $email,
        private readonly string $reqId,
    )
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->ticketUuid)];
    }

    public function tries(): int
    {
        return 3;
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * @throws NotEnoughTicketsAvailable
     */
    public function handle(): void
    {
        $agg = TicketAggregate::retrieve($this->ticketUuid);
        if (count($agg->getAppliedEvents()) > 20) {
            $agg->snapshot();
        }
        $agg->purchaseTicket(
            $this->quantity,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->reqId
        )->persist();
    }
}
