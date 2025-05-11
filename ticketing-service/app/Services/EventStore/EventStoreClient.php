<?php

namespace App\Services\EventStore;

use Event_store\Client\Streams\StreamsClient;
use Grpc\ChannelCredentials;

class EventStoreClient
{
    private StreamsClient $client;

    /**
     * Create a new EventstoreClient instance
     *
     * @param string $host Hostname of the Eventstore server
     * @param int $port Port number of the Eventstore server
     * @param bool $secure Whether to use secure connection
     */
    public function __construct(string $host = 'localhost', int $port = 2113, bool $secure = false)
    {
        $target = "$host:$port";

        // Create the stream client
        $this->client = new StreamsClient($target, [
            'credentials' => $secure ? ChannelCredentials::createSsl() : ChannelCredentials::createInsecure(),
            'grpc.enable_http_proxy' => 0,
        ]);
    }

    public static function make(): self
    {
        return new self(
            config('services.eventstore.host'),
            config('services.eventstore.port')
        );
    }

    public function getClient(): StreamsClient
    {
        return $this->client;
    }

    public function close(): void
    {
        $this->client->close();
    }
}
