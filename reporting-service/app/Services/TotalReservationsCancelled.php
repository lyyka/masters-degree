<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalReservationsCancelled implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->where('is_cancelled', true)->count()
        ];
    }
}
