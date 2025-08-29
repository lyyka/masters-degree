<?php

namespace App\Services\EventStore;

use Cache;

class StreamCache
{
    public function setLatestRevision(string $streamName, int $revision): void
    {
        Cache::put("$streamName-latest-revision", $revision);
    }

    public function getLatestRevision(string $streamName): ?int
    {
        return Cache::remember("$streamName-latest-revision", null, function () use ($streamName) {
            $event = (new ReadStream(EventStoreClient::make()))->latest($streamName)->first();
            return $event !== null ? $event['revision'] : null;
        });
    }

    public function markAsExists(string $streamName): void
    {
        Cache::put($streamName, true);
    }

    public function checkStreamExistence(string $streamName): bool
    {
        return Cache::has($streamName);
    }
}
