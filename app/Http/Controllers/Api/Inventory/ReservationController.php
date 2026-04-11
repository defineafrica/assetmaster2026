<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Reservation\Reservation;
use App\Services\Inventory\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::with(['reserver', 'department', 'creator']);

        if ($request->has('status')) {
            $reservations->where('status', $request->input('status'));
        }

        if ($request->has('item_type')) {
            $reservations->where('item_type', $request->input('item_type'));
        }

        if ($request->has('reserved_by')) {
            $reservations->where('reserved_by', $request->input('reserved_by'));
        }

        if ($request->has('department_id')) {
            $reservations->where('department_id', $request->input('department_id'));
        }

        return response()->json([
            'total' => $reservations->count(),
            'rows' => $reservations->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|max:50|in:consumables,accessories',
            'item_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1',
            'department_id' => 'nullable|integer|exists:departments,id',
            'project_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $availableQty = $this->reservationService->getAvailableQuantity(
            $request->input('item_type'),
            $request->input('item_id')
        );

        $requestedQty = $request->input('quantity', 1);

        if ($availableQty < $requestedQty) {
            return response()->json([
                'messages' => "Insufficient quantity available. Only {$availableQty} available.",
            ], 422);
        }

        $reservation = $this->reservationService->createReservation($request->all());

        return response()->json([
            'messages' => 'Reservation created successfully',
            'reservation' => $reservation->load(['reserver', 'department']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::with(['reserver', 'department', 'creator', 'item'])->find($id);

        if (!$reservation) {
            return response()->json(['messages' => 'Reservation not found'], 404);
        }

        return response()->json($reservation);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['messages' => 'Reservation not found'], 404);
        }

        if (!$reservation->isActive()) {
            return response()->json(['messages' => 'Only active reservations can be updated'], 422);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|integer|min:1',
            'department_id' => 'nullable|integer|exists:departments,id',
            'project_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        if ($request->has('quantity')) {
            $availableQty = $this->reservationService->getAvailableQuantity(
                $reservation->item_type,
                $reservation->item_id
            ) + $reservation->quantity;

            if ($availableQty < $request->input('quantity')) {
                return response()->json([
                    'messages' => "Insufficient quantity. Only {$availableQty} available.",
                ], 422);
            }
        }

        $reservation->update($request->only([
            'quantity', 'department_id', 'project_code', 'notes', 'expires_at'
        ]));
        $reservation->updated_by = auth()->id();
        $reservation->save();

        return response()->json([
            'messages' => 'Reservation updated successfully',
            'reservation' => $reservation->load(['reserver', 'department']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['messages' => 'Reservation not found'], 404);
        }

        if (!in_array($reservation->status, [Reservation::STATUS_ACTIVE, Reservation::STATUS_EXPIRED])) {
            return response()->json(['messages' => 'Reservation cannot be cancelled in current status'], 422);
        }

        $this->reservationService->cancelReservation($reservation);

        return response()->json(['messages' => 'Reservation cancelled successfully']);
    }

    public function fulfill(int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['messages' => 'Reservation not found'], 404);
        }

        if (!$this->reservationService->fulfillReservation($reservation)) {
            return response()->json([
                'messages' => 'Failed to fulfill reservation. Item may no longer be available.',
            ], 422);
        }

        return response()->json([
            'messages' => 'Reservation fulfilled successfully',
            'reservation' => $reservation->fresh()->load(['reserver', 'department']),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['messages' => 'Reservation not found'], 404);
        }

        if (!$this->reservationService->cancelReservation($reservation)) {
            return response()->json(['messages' => 'Reservation cannot be cancelled in current status'], 422);
        }

        return response()->json([
            'messages' => 'Reservation cancelled successfully',
            'reservation' => $reservation->fresh()->load(['reserver', 'department']),
        ]);
    }

    public function availability(string $itemType, int $itemId): JsonResponse
    {
        $availability = $this->reservationService->getItemAvailability($itemType, $itemId);

        return response()->json($availability);
    }

    public function activeReservations(string $itemType, int $itemId): JsonResponse
    {
        $reservations = Reservation::getActiveReservationsForItem($itemType, $itemId)
            ->load(['reserver', 'department']);

        return response()->json([
            'total' => $reservations->count(),
            'reservations' => $reservations,
        ]);
    }
}
