<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('available_quantity');
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->timestamps();
        });
    }
};
