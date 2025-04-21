<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasUuid;

    /** @return BelongsTo<Event, Ticket> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
