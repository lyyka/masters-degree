<?php

namespace App\Repositories;

use App\Services\RabbitMQ\RabbitMQService;
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

class RabbitMQStreamsStoredEventRepository implements StoredEventRepository
{
    private readonly RabbitMQService $rabbitMQService;

    public function __construct()
    {
        $this->rabbitMQService = new RabbitMQService();
    }

    public function find(int $id): StoredEvent
    {
        throw new Exception("Event with ID {$id} not found");
    }

    public function retrieveAll(?string $uuid = null): LazyCollection
    {
        if ($uuid === null) {
            throw new Exception('Cannot retrieveAll without Aggregate UUID in RabbitMQ Streams');
        }

        $events = $this->rabbitMQService->readFromStream($uuid);

        return $events->map(function ($eventData) {
            return $this->arrayToStoredEvent($eventData);
        });
    }

    public function retrieveAllStartingFrom(int $startingFrom, ?string $uuid = null): LazyCollection
    {
        if ($uuid === null) {
            throw new Exception('Cannot retrieveAllStartingFrom without Aggregate UUID in RabbitMQ Streams');
        }

        $events = $this->rabbitMQService->readFromStream($uuid, $startingFrom);

        return $events->map(function ($eventData) {
            return $this->arrayToStoredEvent($eventData);
        });
    }

    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        $events = $this->rabbitMQService->readFromStream($aggregateUuid, $aggregateVersion);

        return $events->map(function ($eventData) {
            return $this->arrayToStoredEvent($eventData);
        });
    }

    public function countAllStartingFrom(int $startingFrom, ?string $uuid = null): int
    {
        return $this->retrieveAllStartingFrom($startingFrom, $uuid)->count();
    }

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

    public function persist(ShouldBeStored $event, ?string $uuid = null): StoredEvent
    {
        if ($uuid === null) {
            throw new Exception('Cannot store event without Aggregate UUID in RabbitMQ Streams');
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

        $eventData = [
            'event_properties' => app($serializerClass)->serialize(clone $event),
            'aggregate_uuid' => $uuid,
            'aggregate_version' => $event->aggregateRootVersion(),
            'event_version' => $event->eventVersion(),
            'event_class' => $this->getEventClass(get_class($event)),
            'created_at' => $createdAt->toDateTimeString(),
        ];

        $fullMetaData = $metaData + [
                MetaData::CREATED_AT => $createdAt->toDateTimeString(),
            ];

        $this->rabbitMQService->publishToStream($uuid, $eventData, $fullMetaData);

        return new StoredEvent([
            'event_properties' => $eventData['event_properties'],
            'aggregate_uuid' => $eventData['aggregate_uuid'],
            'aggregate_version' => $eventData['aggregate_version'],
            'event_version' => $eventData['event_version'],
            'event_class' => $eventData['event_class'],
            'meta_data' => collect($fullMetaData),
            'created_at' => $createdAt,
        ], $event);
    }

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
        // Streams are append-only, updates are not supported
        throw new Exception('Update operation not supported in RabbitMQ Streams (append-only)');
    }

    public function getLatestAggregateVersion(string $aggregateUuid): int
    {
        try {
            return $this->rabbitMQService->getStreamMeta($aggregateUuid);
        } catch (Exception) {
            return 0;
        }
    }

    private function arrayToStoredEvent(array $eventData): StoredEvent
    {
        return new StoredEvent([
            'event_properties' => $eventData['data']['event_properties'],
            'aggregate_uuid' => $eventData['data']['aggregate_uuid'],
            'aggregate_version' => $eventData['data']['aggregate_version'] ?? 0,
            'event_version' => $eventData['data']['event_version'],
            'event_class' => $eventData['data']['event_class'],
            'meta_data' => collect($eventData['meta_data']),
            'created_at' => $eventData['data']['created_at'],
        ]);
    }
}
