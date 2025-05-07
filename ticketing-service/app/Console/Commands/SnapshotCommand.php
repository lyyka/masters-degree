<?php

namespace App\Console\Commands;

use App\Aggregates\TicketAggregate;
use Illuminate\Console\Command;

class SnapshotCommand extends Command
{
    protected $signature = 'app:snapshot';

    public function handle(): void
    {
        TicketAggregate::retrieve(
            $this->ask('Ticket UUID')
        )->snapshot();
    }
}
