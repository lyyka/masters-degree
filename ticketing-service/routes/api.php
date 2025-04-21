<?php

use App\Http\Controllers\TicketController;

Route::prefix('/tickets')->group(function () {
    Route::post('/{ticket:uuid}', [TicketController::class, 'purchase']);
});

Route::prefix('/ticket-reservations')->group(function () {
    Route::patch('/check-in/{ticketReservation:uuid}', [TicketController::class, 'checkIn']);
    Route::patch('/cancel/{ticketReservation:uuid}', [TicketController::class, 'cancel']);
    Route::patch('/update-holder/{ticketReservation:uuid}', [TicketController::class, 'updateHolder']);
});
