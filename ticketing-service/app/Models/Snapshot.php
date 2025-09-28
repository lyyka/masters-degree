<?php

namespace App\Models;

use Spatie\EventSourcing\Snapshots\EloquentSnapshot;

class Snapshot extends EloquentSnapshot
{
    protected $connection = 'vitess';
}
