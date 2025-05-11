<?php

namespace App\Services\EventStore;

use Event_store\Client\UUID;

class EventUUID
{
    public function generate(): UUID
    {
        $uuid = new UUID();
        $uuidString = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $uuid->setString($uuidString);
        return $uuid;
    }
}
