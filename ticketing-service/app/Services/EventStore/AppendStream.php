<?php

namespace App\Services\EventStore;

use Event_store\Client\PBEmpty;
use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\AppendReq;
use Event_store\Client\Streams\AppendReq\Options;
use Event_store\Client\Streams\AppendReq\ProposedMessage;
use Event_store\Client\Streams\AppendResp;
use const Grpc\STATUS_OK;

readonly class AppendStream
{
    private StreamCache $streamCache;

    public function __construct(
        private EventStoreClient $client,
    )
    {
        $this->streamCache = new StreamCache();
    }

    public function write(
        string $streamName,
        string $eventType,
        array  $eventData,
        array  $metadata = []
    ): bool
    {
        $sink = $this->client->getClient()->Append();

        /*
         * Create the header
         *
         * In EventStoreDB, when appending events to a stream via the gRPC API,
         * you must specify a concurrency expectation using the ExpectedStreamRevision in the append request options.
         * This tells EventStoreDB what state you expect the stream to be in during the write,
         * which is crucial for ensuring event consistency and concurrency control.
         */
        $options = new Options();
        $options->setStreamIdentifier(
            (new StreamIdentifier())->setStreamName($streamName)
        );

        if ($this->streamCache->checkStreamExistence($streamName)) {
            $rev = $this->streamCache->getLatestRevision($streamName);
            $options->setRevision($rev);
            $this->streamCache->setLatestRevision($streamName, $rev + 1);
        } else {
            $options->setNoStream(new PBEmpty());
        }

        $header = new AppendReq();
        $header->setOptions($options);
        $sink->write($header);

        // Create the event
        $eventId = app(EventUUID::class)->generate();
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

        $result = $data->getResult();

        return $status->code === STATUS_OK && $result === "success";
    }
}
