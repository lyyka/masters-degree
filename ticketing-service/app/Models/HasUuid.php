<?php

namespace App\Models;

use Illuminate\Support\Str;

trait HasUuid
{
    public static function booted(): void {
        static::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}
