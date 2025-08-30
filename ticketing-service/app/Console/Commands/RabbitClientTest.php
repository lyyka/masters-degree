<?php

namespace App\Console\Commands;

use App\Repositories\RabbitMQStreamsStoredEventRepository;
use Illuminate\Console\Command;

class RabbitClientTest extends Command
{
    protected $signature = 'app:rabbit-client-test';

    public function handle(): void
    {
        //(new RabbitMQStreamsStoredEventRepository())
        //    ->publishToStream('70e06ba3-e92b-3d7e-be79-fb2b83a5dd09', ['data' => 'adfiwfe'], []);
        //(new RabbitMQStreamsStoredEventRepository())
        //    ->publishToStream('70e06ba3-e92b-3d7e-be79-fb2b83a5dd09', ['data' => 'betbere'], []);
        //(new RabbitMQStreamsStoredEventRepository())
        //    ->publishToStream('70e06ba3-e92b-3d7e-be79-fb2b83a5dd09', ['data' => 'nobetjneo'], []);
        dd(
            (new RabbitMQStreamsStoredEventRepository())
                ->readFromStream('70e06ba3-e92b-3d7e-be79-fb2b83a5dd09')
                ->all()
        );
    }
}
