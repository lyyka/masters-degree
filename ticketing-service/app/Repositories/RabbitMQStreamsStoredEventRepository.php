<?php

namespace App\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\LazyCollection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
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
    private const string EVENT_STREAM_NAME = 'events_stream';

    private ?AMQPStreamConnection $connection = null;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password'),
            config('queue.connections.rabbitmq.hosts.0.vhost'),
        );
    }

    private function ensureConnection(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connect();
        }
    }

    private function getStreamName(string $aggregateUuid): string
    {
        return self::EVENT_STREAM_NAME . '_' . $aggregateUuid;
    }

    private function declareStream(string $streamName): void
    {
        $channel = $this->connection->channel();

        // Declare stream as a durable queue with stream arguments
        $channel->queue_declare(
            $streamName,
            false,     // passive
            true,      // durable
            false,     // exclusive
            false,     // auto_delete
            false,     // nowait
            [
                'x-queue-type' => ['S', 'stream'],
                //'x-max-length-bytes' => ['I', 1000000000], // 1GB max
            ]
        );

        $channel->close();
    }

    public function publishToStream(string $streamName, array $eventData, array $metaData): void
    {
        $streamName = $this->getStreamName($streamName);

        $this->ensureConnection();
        $channel = $this->connection->channel();

        // Ensure stream exists
        $this->declareStream($streamName);

        $messageBody = json_encode([
            'data' => $eventData,
            'meta_data' => $metaData,
        ]);

        $message = new AMQPMessage($messageBody, [
            'delivery_mode' => 2, // Make message persistent
            'timestamp' => time(),
        ]);

        $channel->basic_publish($message, '', $streamName);
        $channel->close();
    }

    public function readFromStream(string $streamName, ?int $offset = null): LazyCollection
    {
        $streamName = $this->getStreamName($streamName);

        $this->ensureConnection();
        $channel = $this->connection->channel();

        try {
            $this->declareStream($streamName);
        } catch (Exception) {
            return new LazyCollection([]);
        }

        $messages = [];

        // Simple consumer to read messages
        $callback = function (AMQPMessage $message) use (&$messages) {
            $messageData = json_decode($message->getBody(), true);
            $messages[] = $messageData;
            $message->ack();
        };

        $channel->basic_qos(0, 100, false);
        $opts = $offset !== null ? ['x-stream-offset' => $offset] : ['x-stream-offset' => 'first'];
        $channel->basic_consume($streamName, '', false, false, false, false, $callback, null, new AMQPTable($opts));

        while ($channel->is_consuming()) {
            try {
                $channel->wait(null, false, 0.1);
            } catch (Exception) {
                break;
            }
        }

        $channel->close();

        return new LazyCollection($messages);
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

        $events = $this->readFromStream($uuid);

        return $events->map(function ($eventData) {
            return $this->arrayToStoredEvent($eventData);
        });
    }

    public function retrieveAllStartingFrom(int $startingFrom, ?string $uuid = null): LazyCollection
    {
        if ($uuid === null) {
            throw new Exception('Cannot retrieveAllStartingFrom without Aggregate UUID in RabbitMQ Streams');
        }

        $events = $this->readFromStream($uuid, $startingFrom);

        return $events->map(function ($eventData) {
            return $this->arrayToStoredEvent($eventData);
        });
    }

    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        $events = $this->readFromStream($aggregateUuid, $aggregateVersion);

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

        $this->ensureConnection();
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

        $eventId = $this->publishToStream($uuid, $eventData, $fullMetaData);

        return new StoredEvent([
            'id' => $eventId,
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
            $events = $this->readFromStream($aggregateUuid);
            $latestEvent = $events->last();

            return $latestEvent ? $latestEvent['data']['aggregate_version'] + 1 : 0;
        } catch (Exception) {
            return 0;
        }
    }

    private function arrayToStoredEvent(array $eventData): StoredEvent
    {
        return new StoredEvent([
            'id' => $eventData['id'],
            'event_properties' => $eventData['data']['event_properties'],
            'aggregate_uuid' => $eventData['data']['aggregate_uuid'],
            'aggregate_version' => $eventData['data']['aggregate_version'] ?? 0,
            'event_version' => $eventData['data']['event_version'],
            'event_class' => $eventData['data']['event_class'],
            'meta_data' => collect($eventData['meta_data']),
            'created_at' => $eventData['data']['created_at'],
        ]);
    }

    public function __destruct()
    {
        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
