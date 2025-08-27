<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasUuid;

    protected $guarded = [];

    /** @return BelongsTo<Event, Ticket> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
