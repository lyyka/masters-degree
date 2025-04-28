<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_cancelled' => 'boolean',
            'is_checked_in' => 'boolean',
            'is_ticket_holder_updated' => 'boolean',
        ];
    }
}
