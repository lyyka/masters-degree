<?php

namespace App\Jobs\Messages;

use App\Jobs\Messages\Dto\TicketReservationChangeDto;
use App\Models\TicketReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TicketReservationCheckedInMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $data
    )
    {
    }

    public function handle(): void
    {
        $dto = new TicketReservationChangeDto($this->data);

        TicketReport::where('reservation_uuid', $dto->getTicketReservationUuid())->update([
            'is_checked_in' => true,
        ]);
    }
}
