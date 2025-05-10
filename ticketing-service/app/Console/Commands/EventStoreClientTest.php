<?php

namespace App\Console\Commands;

use App\Services\EventStore\EventStoreClient;
use Illuminate\Console\Command;

class EventStoreClientTest extends Command
{
    protected $signature = 'app:event-store-client-test';

    public function handle(): void
    {
        $client = new EventStoreClient(
            config('services.eventstore.host'),
            config('services.eventstore.port'),
        );

        // Define event data
        $eventData = [
            'userId' => 123,
            'action' => 'user_registered',
            'timestamp' => date('c')
        ];

        $success = $client->appendToStream(
            'users',           // Stream name
            'UserRegistered',  // Event type
            $eventData,        // Event data
            []          // Metadata
        );

        $this->info($success);

        $client->close();
    }
}
