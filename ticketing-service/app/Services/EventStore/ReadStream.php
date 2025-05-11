<?php

namespace App\Services\EventStore;

use Event_store\Client\PBEmpty;
use Event_store\Client\StreamIdentifier;
use Event_store\Client\Streams\ReadReq;
use Event_store\Client\Streams\ReadReq\Options\ReadDirection;
use Event_store\Client\Streams\ReadResp;

readonly class ReadStream
{
    public function __construct(
        private EventStoreClient $client
    )
    {
    }

    public function latest(string $streamName): array
    {
        return $this->readInt(
            $streamName,
            ReadReq\Options\ReadDirection::Backwards,
            null,
            1
        );
    }

    /**
     * @return array[]
     */
    public function read(
        string $streamName,
        int    $revision = null,
        int    $limit = null
    ): array
    {
        return $this->readInt(
            $streamName,
            ReadReq\Options\ReadDirection::Forwards,
            $revision,
            $limit
        );
    }

    private function readInt(
        string $streamName,
        int    $readDirection,
        int    $revision = null,
        int    $limit = null,
    ): array
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

        $readReq = new ReadReq();
        $readReq->setOptions($options);

        $read = $this->client->getClient()->Read($readReq);

        $result = [];
        /** @var ReadResp $response */
        foreach ($read->responses() as $response) {
            $event = $response->getEvent()->getEvent();
            $result[] = [
                'revision' => $event->getStreamRevision(),
                'data' => $event->getData()
            ];
        }
        return $result;
    }
}
