<?php

namespace App\Services\EventStore;

use Event_store\Client\PBEmpty;
use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\AppendReq;
use Event_store\Client\Streams\AppendReq\Options;
use Event_store\Client\Streams\AppendReq\ProposedMessage;
use Event_store\Client\Streams\AppendResp;
use Event_store\Client\Streams\StreamsClient;
use Event_store\Client\UUID;
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

    /**
     * Generate a UUID for event ID
     *
     * @return UUID
     */
    public function generateUuid(): UUID
    {
        $uuid = new UUID();
        $uuidString = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $uuid->setString($uuidString);
        return $uuid;
    }

    /**
     * Write an event to a stream
     *
     * @param string $streamName Name of the stream
     * @param string $eventType Type of the event
     * @param array $eventData Event data
     * @param array $metadata Optional metadata
     * @return bool Success status
     */
    public function appendToStream(string $streamName, string $eventType, array $eventData, array $metadata = []): bool
    {
        $sink = $this->client->Append();

        // Create the header
        /*
         * In EventStoreDB, when appending events to a stream via the gRPC API,
         * you must specify a concurrency expectation using the ExpectedStreamRevision in the append request options.
         * This tells EventStoreDB what state you expect the stream to be in during the write,
         * which is crucial for ensuring event consistency and concurrency control.
         */
        $options = new Options();
        $options->setStreamIdentifier(
            (new StreamIdentifier())->setStreamName($streamName)
        );
        $options->setNoStream(new PBEmpty());
        $header = new AppendReq();
        $header->setOptions($options);
        $sink->write($header);

        // Create the event
        $eventId = $this->generateUuid();
        $event = new ProposedMessage();
        $event->setId($eventId);
        $event->setData(
            json_encode($eventData)
        );
        $event->setCustomMetadata(
            json_encode($metadata)
        );
        $event->setMetadata([
            'type' => $eventType,
            'content-type' => 'application/json'
        ]);

        // Create the append request
        $request = new AppendReq();
        $request->setProposedMessage($event);
        $sink->write($request);

        /** @var AppendResp $data */
        [$data, $status] = $sink->wait();

        return $status->code === \Grpc\STATUS_OK;
    }

    /**
     * Close the connection
     */
    public function close(): void
    {
        $this->client->close();
    }
}
