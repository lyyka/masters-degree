<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalTicketHolderUpdatedTimes implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->sum('ticket_holder_updated_times_count')
        ];
    }
}
