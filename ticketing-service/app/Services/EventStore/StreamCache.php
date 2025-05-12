<?php

namespace App\Services\EventStore;

use Cache;

class StreamCache
{
    public function setLatestRevision(string $streamName, int $revision): void
    {
        Cache::put("$streamName-latest-revision", $revision);
    }

    public function getLatestRevision(string $streamName): int
    {
        return Cache::remember("$streamName-latest-revision", null, function () use ($streamName) {
            $event = (new ReadStream(EventStoreClient::make()))->latest($streamName)->first();
            return $event !== null ? $event['revision'] : 0;
        });
    }

    public function checkStreamExistence(string $streamName): bool
    {
        $exists = Cache::has($streamName);

        if (!$exists) {
            Cache::put($streamName, true);
        }

        return $exists;
    }
}
