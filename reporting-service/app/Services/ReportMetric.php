<?php

namespace App\Services;

enum ReportMetric: string
{
    case TotalTickets = 'total_tickets';
    case TotalReservations = 'total_reservations';
    case TotalReservationsValue = 'total_reservations_value';

    case TotalCheckedIn = 'total_checked_in';
    case TotalCancelled = 'total_cancelled';
    case TotalTicketHoldersUpdated = 'total_ticket_holders_updated';
    case TotalTicketHoldersUpdatedTimes = 'total_ticket_holders_updated_times';

    public function getHandler(): ReportMetricHandler
    {
        return match ($this) {
            self::TotalTickets => new TotalTickets(),
            self::TotalReservations => new TotalReservations(),
            self::TotalReservationsValue => new TotalReservationsValue(),

            self::TotalCheckedIn => new TotalReservationsCheckedIn(),
            self::TotalCancelled => new TotalReservationsCancelled(),
            self::TotalTicketHoldersUpdated => new TotalReservationsTicketHolderUpdated(),
            self::TotalTicketHoldersUpdatedTimes => new TotalTicketHolderUpdatedTimes(),
        };
    }
}
