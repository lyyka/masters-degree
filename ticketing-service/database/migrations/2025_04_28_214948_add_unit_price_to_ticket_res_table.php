<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ticket_reservations', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->after('quantity');
        });
    }
};
