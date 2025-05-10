<?php

namespace App\Services\EventStore;

use Event_store\Client\PBEmpty;
use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\AppendReq;
use Event_store\Client\Streams\AppendReq\Options;
use Event_store\Client\Streams\AppendReq\ProposedMessage;
use Event_store\Client\Streams\AppendResp;
use Event_store\Client\Streams\ReadReq;
use Event_store\Client\Streams\ReadResp;
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

    public function readFromStream(string $streamName): void
    {
        $options = new ReadReq\Options();
        $options->setResolveLinks(false);
        $options->setUuidOption((new ReadReq\Options\UUIDOption())->setString(new PBEmpty()));
        $options->setReadDirection(ReadReq\Options\ReadDirection::Backwards);
        $options->setCount(1);
        $options->setNoFilter(new PBEmpty());
        $options->setStream(
            (new ReadReq\Options\StreamOptions())
                ->setStreamIdentifier((new StreamIdentifier())->setStreamName($streamName))
                //->setRevision(2)
                ->setEnd(new PBEmpty())
        );

        $readReq = new ReadReq();
        $readReq->setOptions($options);

        $read = $this->client->Read($readReq);

        /** @var ReadResp $response */
        foreach ($read->responses() as $response) {
            dd($response->getEvent()->getEvent()->getData());
        }
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
        /*
         *  setNoStream when first creating the stream (use some sort of cache to determine if streams exist or not)
         *  setRevision (latest stream revision) when stream exists
         *
         */
        $options->setRevision(2);
        //$options->setNoStream(new PBEmpty());
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
