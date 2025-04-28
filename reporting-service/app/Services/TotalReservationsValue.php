<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalReservationsValue implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->sum('total_price')
        ];
    }
}
