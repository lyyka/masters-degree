<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_uuid')->unique();
            $table->string('ticket_uuid');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('total_price');
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_checked_in')->default(false);
            $table->boolean('is_ticket_holder_updated')->default(false);
            $table->unsignedInteger('ticket_holder_updated_times_count')->default(0);
            $table->timestamps();
        });
    }
};
