<?php

namespace App\Services\EventStore;

use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\AppendReq;
use Event_store\Client\Streams\AppendReq\Options;
use Event_store\Client\Streams\AppendReq\ProposedMessage;
use Event_store\Client\Streams\StreamsClient;
use Event_store\Client\UUID;
use Google\Protobuf\Any;
use Google\Protobuf\Timestamp;
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
        // Create event ID
        $eventId = $this->generateUuid();

        // Create timestamp
        $timestamp = new Timestamp();
        $now = new \DateTime();
        $timestamp->setSeconds($now->getTimestamp());
        $timestamp->setNanos(0);

        // Create event data
        $data = new Any();
        $data->setValue(json_encode($eventData));

        // Create metadata
        $metadataObj = new Any();
        $metadataObj->setValue(json_encode($metadata));

        // Create the event
        $event = new ProposedMessage();
        $event->setId($eventId);
        $event->setData($data->getValue());
        $event->setCustomMetadata($metadataObj->getValue());

        // Set content type to JSON
        $event->setMetadata([
            'type' => 'test-event',
            'content-type' => 'application/json'
        ]);

        // Create the append options
        $options = new Options();
        $options->setStreamIdentifier(new StreamIdentifier([$streamName]));

        // Create the append request
        $request = new AppendReq();
        $request->setOptions($options);
        $request->setProposedMessage($event);

        // Write to stream
        list($response, $status) = $this->client->Append($request)->wait();

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
