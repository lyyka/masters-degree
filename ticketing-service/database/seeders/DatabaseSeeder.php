<?php

namespace Database\Seeders;

use Database\Factories\EventFactory;
use Database\Factories\TicketFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $davidGuettaConcert = EventFactory::new()->create([
            'name' => 'David Guetta Concert',
            'venue_name' => 'Stockholm Avicii Arena',
            'venue_address' => '121 77 Johanneshov, Sweden',
        ]);

        TicketFactory::new()->forEvent($davidGuettaConcert)
            ->create([
                'name' => 'Standard Ticket',
                'price' => 150,
                'available_quantity' => 1000
            ]);

        TicketFactory::new()->forEvent($davidGuettaConcert)
            ->create([
                'name' => 'VIP Ticket',
                'price' => 500,
                'available_quantity' => 500
            ]);

        TicketFactory::new()->forEvent($davidGuettaConcert)
            ->create([
                'name' => 'Meet & Greet Ticket',
                'price' => 1500,
                'available_quantity' => 50
            ]);
    }
}
