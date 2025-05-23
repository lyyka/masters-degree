<?php

namespace App\Services\EventStore;

use Event_store\Client\PBEmpty;
use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\ReadReq;
use Event_store\Client\Streams\ReadReq\Options\ReadDirection;
use Event_store\Client\Streams\ReadResp;
use Illuminate\Support\LazyCollection;
use Log;

readonly class ReadStream
{
    public function __construct(
        private EventStoreClient $client
    )
    {
    }

    public function latest(string $streamName, string $eventTypeMatch = null): LazyCollection
    {
        return $this->readInt(
            $streamName,
            ReadReq\Options\ReadDirection::Backwards,
            null,
            1,
            $eventTypeMatch
        );
    }

    public function read(
        string $streamName,
        int    $revision = null,
        int    $limit = null,
        string $eventTypeMatch = null,
    ): LazyCollection
    {
        return $this->readInt(
            $streamName,
            ReadReq\Options\ReadDirection::Forwards,
            $revision,
            $limit,
            $eventTypeMatch
        );
    }

    private function readInt(
        string $streamName,
        int    $readDirection,
        int    $revision = null,
        int    $limit = null,
        string $eventTypeMatch = null,
    ): LazyCollection
    {
        $streamOptions = (new ReadReq\Options\StreamOptions())
            ->setStreamIdentifier((new StreamIdentifier())->setStreamName($streamName));

        if ($revision !== null) {
            $streamOptions->setRevision($revision);
        } else {
            if ($readDirection === ReadDirection::Forwards) {
                $streamOptions->setStart(new PBEmpty());
            } else {
                $streamOptions->setEnd(new PBEmpty());
            }
        }

        $options = new ReadReq\Options();
        $options->setResolveLinks(false);
        $options->setUuidOption((new ReadReq\Options\UUIDOption())->setString(new PBEmpty()));
        $options->setReadDirection($readDirection);
        if ($limit !== null) {
            $options->setCount($limit);
        }
        $options->setNoFilter(new PBEmpty());
        $options->setStream($streamOptions);
        if ($eventTypeMatch !== null) {
            $options->setFilter(
                (new ReadReq\Options\FilterOptions())
                    ->setEventType(
                        (new ReadReq\Options\FilterOptions\Expression())
                            ->setPrefix([$eventTypeMatch])
                    )
            );
        }

        $readReq = new ReadReq();
        $readReq->setOptions($options);

        $read = $this->client->getClient()->Read($readReq);

        return LazyCollection::make(function () use ($read) {
            /** @var ReadResp $response */
            foreach ($read->responses() as $response) {
                if ($response->hasEvent()) {
                    $event = $response->getEvent()->getEvent();
                    yield [
                        'revision' => $event->getStreamRevision(),
                        'data' => json_decode($event->getData()),
                        'type' => (string)$event->getMetadata()->offsetGet('type'),
                        'custom_metadata' => json_decode($event->getCustomMetadata())
                    ];
                } else {
                    Log::info($response->serializeToString());
                }
            }
        });
    }
}
