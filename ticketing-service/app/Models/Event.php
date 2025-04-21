<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'date' => 'date'
        ];
    }

    /** @return HasMany<Ticket, Event> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
