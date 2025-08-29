<?php

namespace App\Console\Commands;

use App\Services\EventStore\AppendStream;
use App\Services\EventStore\EventStoreClient;
use App\Services\EventStore\ReadStream;
use Illuminate\Console\Command;

class EventStoreClientTest extends Command
{
    protected $signature = 'app:event-store-client-test';

    public function handle(): void
    {
        $client = EventStoreClient::make();
        $appendStream = new AppendStream($client);
        $readStream = new ReadStream($client);

        dd(
            $readStream->latest(
                'events-1'
            )->all()
        );

        //$appendStream->write(
        //    'users',           // Stream name
        //    'UserRegistered',  // Event type
        //    [
        //        'userId' => 123,
        //        'action' => 'user_registered',
        //        'timestamp' => date('c')
        //    ]
        //);

        $client->close();
    }
}
