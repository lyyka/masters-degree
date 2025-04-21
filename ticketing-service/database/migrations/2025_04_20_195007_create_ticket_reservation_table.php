<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $table->uuid('ticket_uuid')->unique();
            $table->unsignedInteger('quantity');
            $table->string('holder_first_name');
            $table->string('holder_last_name');
            $table->string('holder_email');
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }
};
