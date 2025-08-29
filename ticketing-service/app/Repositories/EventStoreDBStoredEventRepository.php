<?php

namespace App\Repositories;

use App\Services\EventStore\AppendStream;
use App\Services\EventStore\EventStoreClient;
use App\Services\EventStore\ReadStream;
use App\Services\EventStore\StreamCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\LazyCollection;
use ReflectionClass;
use Spatie\EventSourcing\Attributes\EventSerializer as EventSerializerAttribute;
use Spatie\EventSourcing\Enums\MetaData;
use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Spatie\EventSourcing\StoredEvents\Exceptions\EventClassMapMissing;
use Spatie\EventSourcing\StoredEvents\Repositories\StoredEventRepository;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class EventStoreDBStoredEventRepository implements StoredEventRepository
{
    private const string EVENT_STREAM_NAME = 'events-1';

    private readonly AppendStream $appendStream;
    private readonly ReadStream $readStream;
    private readonly StreamCache $streamCache;

    public function __construct()
    {
        $client = EventStoreClient::make();
        $this->appendStream = new AppendStream($client);
        $this->readStream = new ReadStream($client);
        $this->streamCache = new StreamCache();
    }

    private function eventToStoredEvent(mixed $event): StoredEvent
    {
        $event = json_decode(json_encode($event), true);
        return is_array($event) ? new StoredEvent(
            [
                'id' => $event['revision'],
                'event_properties' => $event['data']['event_properties'],
                'aggregate_uuid' => $event['data']['aggregate_uuid'],
                'aggregate_version' => $event['data']['aggregate_version'] ?? 0,
                'event_version' => $event['data']['event_version'],
                'event_class' => $event['data']['event_class'],
                'meta_data' => collect($event['custom_metadata']),
                'created_at' => $event['data']['created_at'],
            ],
        ) : new StoredEvent(
            [
                'id' => $event->revision,
                'event_properties' => $event->data->event_properties,
                'aggregate_uuid' => $event->data->aggregate_uuid,
                'aggregate_version' => $event->data->aggregate_version ?? 0,
                'event_version' => $event->data->event_version,
                'event_class' => $event->data->event_class,
                'meta_data' => $event->custom_metadata,
                'created_at' => $event->data->created_at,
            ],
        );
    }

    /**
     * @param int $id We use $id as the revision number of an event
     */
    public function find(int $id): StoredEvent
    {
        $event = $this->readStream->read(
            self::EVENT_STREAM_NAME,
            $id,
            1
        )->first();

        return $this->eventToStoredEvent($event);
    }

    /**
     * @throws Exception
     */
    public function retrieveAll(?string $uuid = null): LazyCollection
    {
        if ($uuid === null) {
            throw new Exception('Cannot retrieveAll without Aggregate UUID in EventStoreDB');
        }

        $events = $this->readStream->read(
            self::EVENT_STREAM_NAME,
            null,
            1000,
            $uuid
        );

        return $events->map(function ($event) {
            return $this->eventToStoredEvent($event);
        });
    }

    /**
     * @throws Exception
     */
    public function retrieveAllStartingFrom(int $startingFrom, ?string $uuid = null): LazyCollection
    {
        if ($uuid === null) {
            throw new Exception('Cannot retrieveAllStartingFrom without Aggregate UUID in EventStoreDB');
        }

        $events = $this->readStream->read(
            self::EVENT_STREAM_NAME,
            $startingFrom,
            1000,
            $uuid
        );

        return $events->map(function ($event) {
            return $this->eventToStoredEvent($event);
        });
    }

    /**
     * @throws Exception
     */
    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        //  This method actually filters events by version inside the aggregate,
        //  so we cannot use $aggregateVersion as a revision, since we should look for
        //  $aggregateVersion inside all events under $aggregateUuid.

        $events = $this->retrieveAll($aggregateUuid);

        return $events->filter(function (array $event) use ($aggregateVersion) {
            return $event['aggregate_version'] > $aggregateVersion;
        });
    }

    /**
     * @throws Exception
     */
    public function countAllStartingFrom(int $startingFrom, ?string $uuid = null): int
    {
        return $this->retrieveAllStartingFrom($startingFrom, $uuid)->count();
    }

    /**
     * @throws EventClassMapMissing
     */
    private function getEventClass(string $class): string
    {
        $map = config('event-sourcing.event_class_map', []);
        $isMappingEnforced = config('event-sourcing.enforce_event_class_map', false);

        if (!empty($map) && in_array($class, $map)) {
            return array_search($class, $map, true);
        }

        if ($isMappingEnforced) {
            throw EventClassMapMissing::noEventClassMappingProvided($class);
        }

        return $class;
    }

    /**
     * @throws Exception
     */
    public function persist(ShouldBeStored $event, ?string $uuid = null): StoredEvent
    {
        if ($uuid === null) {
            throw new Exception('Cannot store event without Aggregate UUID in EventStoreDB');
        }

        $createdAt = Carbon::now();
        $reflectionClass = new ReflectionClass(get_class($event));

        $serializerClass = EventSerializer::class;
        if ($serializerAttribute = $reflectionClass->getAttributes(EventSerializerAttribute::class)[0] ?? null) {
            $serializerClass = $serializerAttribute->newInstance()->serializerClass;
        }

        $metaData = $event->metaData();
        if ($metaDataCreatedAt = data_get($metaData, MetaData::CREATED_AT)) {
            $metaData[MetaData::CREATED_AT] = $metaDataCreatedAt->toDateTimeString();
        }

        $rev = $this->streamCache->getLatestRevision(self::EVENT_STREAM_NAME) + 1;
        $eventData = [
            'event_properties' => app($serializerClass)->serialize(clone $event),
            'aggregate_uuid' => $uuid,
            'aggregate_version' => $event->aggregateRootVersion(),
            'event_version' => $event->eventVersion(),
            'event_class' => $this->getEventClass(get_class($event)),
            'created_at' => $createdAt,
        ];
        $this->appendStream->write(
            self::EVENT_STREAM_NAME,
            $uuid,
            $eventData,
            $metaData + [
                MetaData::CREATED_AT => $createdAt->toDateTimeString(),
                MetaData::STORED_EVENT_ID => $rev
            ]
        );

        return new StoredEvent(
            [
                'id' => $rev,
                'event_properties' => $eventData['event_properties'],
                'aggregate_uuid' => $eventData['aggregate_uuid'],
                'aggregate_version' => $eventData['aggregate_version'] ?? 0,
                'event_version' => $eventData['event_version'],
                'event_class' => $eventData['event_class'],
                'meta_data' => $metaData,
                'created_at' => $eventData['created_at'],
            ],
            $event
        );
    }

    /**
     * @param ShouldBeStored[] $events
     * @throws Exception
     */
    public function persistMany(array $events, ?string $uuid = null): LazyCollection
    {
        $storedEvents = [];

        foreach ($events as $event) {
            $storedEvents[] = $this->persist($event, $uuid);
        }

        return new LazyCollection($storedEvents);
    }

    public function update(StoredEvent $storedEvent): StoredEvent
    {
        // TODO: Implement update() method.
        return $storedEvent;
    }

    public function getLatestAggregateVersion(string $aggregateUuid): int
    {
        return $this->streamCache->getLatestRevision(self::EVENT_STREAM_NAME) + 1;
    }
}
