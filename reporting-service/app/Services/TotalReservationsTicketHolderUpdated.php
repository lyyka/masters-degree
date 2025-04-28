<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalReservationsTicketHolderUpdated implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->where('is_ticket_holder_updated', true)->count()
        ];
    }
}
