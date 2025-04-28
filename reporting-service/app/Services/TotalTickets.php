<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalTickets implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->sum('quantity')
        ];
    }
}
