<?php

namespace App\Jobs\Messages;

use App\Jobs\Messages\Dto\TicketReservationChangeDto;
use App\Models\TicketReport;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TicketReservationHolderUpdatedMessage implements ShouldQueue
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
            'is_ticket_holder_updated' => true,
            'ticket_holder_updated_times_count' => DB::raw('ticket_holder_updated_times_count + 1'),
        ]);
    }
}
