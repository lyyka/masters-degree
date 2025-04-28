<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalReservations implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->count()
        ];
    }
}
