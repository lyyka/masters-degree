<?php

namespace App\Services;

use App\Models\TicketReport;

class TotalReservationsCheckedIn implements ReportMetricHandler
{
    public function getValue(): array
    {
        return [
            'value' => TicketReport::query()->where('is_checked_in', true)->count()
        ];
    }
}
