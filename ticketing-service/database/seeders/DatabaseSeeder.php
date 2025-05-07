<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = DB::table('events')->latest('id')->first()?->id + 1;

        for ($i = 0; $i < 500; $i++) {
            $eventInserts = [];
            $ticketInserts = [];

            for ($j = 0; $j < 1000; $j++) {
                $eventInserts[] = [
                    'uuid' => fake()->uuid(),
                    'name' => 'David Guetta Concert',
                    'venue_name' => 'Stockholm Avicii Arena',
                    'venue_address' => '121 77 Johanneshov, Sweden',
                    'description' => 'The best concert in the world.',
                    'date' => '2025-01-01'
                ];

                for ($z = 0; $z < 2; $z++) {
                    $ticketInserts[] = [
                        'event_id' => $eventId,
                        'uuid' => fake()->uuid(),
                        'name' => "Tier $z Ticket",
                        'price' => 500,
                        'available_quantity' => 999_999_999
                    ];
                }

                $eventId++;
            }

            DB::table('events')->insert($eventInserts);
            DB::table('tickets')->insert($ticketInserts);
        }
    }
}
