<?php

namespace App\Jobs\Messages;

use App\Jobs\Messages\Dto\TicketPurchasedMessageDto;
use App\Models\TicketReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TicketPurchasedMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $data
    )
    {
    }

    public function handle(): void
    {
        $dto = new TicketPurchasedMessageDto($this->data);
        TicketReport::create([
            'reservation_uuid' => $dto->getTicketReservationUuid(),
            'ticket_uuid' => $dto->getTicketUuid(),
            'quantity' => $dto->getQuantity(),
            'unit_price' => $dto->getUnitPrice(),
            'total_price' => $dto->getQuantity() * $dto->getUnitPrice(),
        ]);
    }
}
