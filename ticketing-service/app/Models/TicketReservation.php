<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\Projections\Projection;

class TicketReservation extends Projection
{
    protected $guarded = [];

    /** @return BelongsTo<Event, TicketReservation> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Ticket, TicketReservation> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
