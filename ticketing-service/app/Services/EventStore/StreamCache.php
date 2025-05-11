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
            $events = (new ReadStream(EventStoreClient::make()))->latest($streamName);
            return ($events[0] ?? null) ? $events[0]['revision'] : null;
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
