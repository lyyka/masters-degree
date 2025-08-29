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

        //$appendStream->write(
        //    'efbab1ea-9de1-3719-b61f-063850927bf1',
        //    'efbab1ea-9de1-3719-b61f-063850927bf1',
        //    ['TEST' => 'please workkkkkk'],
        //);

        dd(
            $readStream->read(
                '764b48d8-b65a-3b0f-99db-d49e34e45d74',
                21 - 1,
                1000
            )->first()
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
