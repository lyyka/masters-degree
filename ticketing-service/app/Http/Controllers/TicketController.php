<?php

namespace App\Http\Controllers;

use App\Exceptions\NotEnoughTicketsAvailable;
use App\Exceptions\TicketReservationAlreadyCancelled;
use App\Exceptions\TicketReservationAlreadyCheckedIn;
use App\Exceptions\TicketReservationCannotBeCheckedIn;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\UpdateHolderRequest;
use App\Models\Ticket;
use App\Models\TicketReservation;
use App\Services\TicketReservationService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function purchase(PurchaseRequest $request, TicketReservationService $service, Ticket $ticket): JsonResponse
    {
        try {
            $service->purchaseTicket($request, $ticket);
        } catch (NotEnoughTicketsAvailable) {
            return response()->json(['message' => 'Not enough tickets available'], 400);
        }

        return response()->json();
    }

    public function checkIn(TicketReservationService $service, TicketReservation $ticketReservation): JsonResponse
    {
        try {
            $service->checkInTicketReservation($ticketReservation);
        } catch (TicketReservationAlreadyCheckedIn) {
            return response()->json(['message' => 'Ticket reservation already checked in'], 400);
        } catch (TicketReservationCannotBeCheckedIn) {
            return response()->json(['message' => 'Ticket reservation cannot be checked in'], 400);
        }

        return response()->json();
    }

    public function cancel(TicketReservationService $service, TicketReservation $ticketReservation): JsonResponse
    {
        try {
            $service->cancelTicketReservation($ticketReservation);
        } catch (TicketReservationAlreadyCancelled) {
            return response()->json(['message' => 'Ticket reservation already cancelled'], 400);
        }

        return response()->json();
    }

    public function updateHolder(UpdateHolderRequest $request, TicketReservationService $service, TicketReservation $ticketReservation): JsonResponse
    {
        try {
            $service->updateTicketReservationHolder($request, $ticketReservation);
        } catch (TicketReservationAlreadyCheckedIn) {
            return response()->json(['message' => 'Ticket reservation already checked in'], 400);
        } catch (TicketReservationAlreadyCancelled) {
            return response()->json(['message' => 'Ticket reservation already cancelled'], 400);
        }

        return response()->json();
    }
}
