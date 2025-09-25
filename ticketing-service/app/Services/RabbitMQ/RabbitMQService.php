<?php

namespace App\Services\RabbitMQ;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQService
{
    private const string EVENT_STREAM_NAME = 'events_stream';

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

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

    private function getChannel(): AMQPChannel
    {
        // Reuse existing channel if available and open
        if ($this->channel && $this->channel->is_open()) {
            return $this->channel;
        }

        // Create new channel
        $this->channel = $this->connection->channel();

        // Enable publisher confirms for reliability
        $this->channel->confirm_select();

        return $this->channel;
    }

    private function declareStream(string $streamName): void
    {
        $channel = $this->getChannel();

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

        //$channel->close();
    }

    public function getStreamMeta(string $streamName): int
    {
        $streamName = $this->getStreamName($streamName);

        return Cache::rememberForever("$streamName.meta", function () use ($streamName) {
            $channel = $this->getChannel();

            list($queueName, $messageCount, $consumerCount) = $channel->queue_declare(
                $streamName,
                true
            );

            //$channel->close();

            return $messageCount;
        });
    }

    public function publishToStream(string $streamName, array $eventData, array $metaData): void
    {
        $streamName = $this->getStreamName($streamName);

        $this->ensureConnection();
        $channel = $this->getChannel();

        //$channel->confirm_select();

        // Set up callbacks
        //$channel->set_ack_handler(
        //    function (AMQPMessage $message) use ($streamName) {
        //        Cache::increment("$streamName.meta");
        //    }
        //);

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

        //$channel->wait_for_pending_acks(5.0);
        Cache::increment("$streamName.meta");

        //$channel->close();
    }

    public function readFromStream(string $streamName, ?int $offset = null): LazyCollection
    {
        $streamName = $this->getStreamName($streamName);

        $this->ensureConnection();
        $channel = $this->getChannel();

        try {
            $this->declareStream($streamName);
        } catch (Exception) {
            return new LazyCollection([]);
        }

        $messages = [];
        $latestDeliveryTag = 0;

        $callback = function (AMQPMessage $message) use (&$messages, &$latestDeliveryTag) {
            $messageData = json_decode($message->getBody(), true);
            $messages[] = $messageData;
            $latestDeliveryTag = $message->getDeliveryTag();
        };

        $channel->basic_qos(0, 100, false);
        $opts = $offset !== null ? ['x-stream-offset' => $offset] : ['x-stream-offset' => 'first'];
        $channel->basic_consume($streamName, '', false, false, false, false, $callback, null, new AMQPTable($opts));

        while ($channel->is_consuming()) {
            try {
                $channel->wait(null, false, 0.01);
            } catch (Exception) {
                break;
            }
        }

        $channel->basic_ack($latestDeliveryTag, true);

        return new LazyCollection($messages);
    }
}
