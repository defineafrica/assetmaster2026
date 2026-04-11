<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Reservation\Reservation;
use App\Services\Inventory\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function index(): View
    {
        return view('inventory/reservations/index');
    }

    public function create(Request $request): View
    {
        $itemType = $request->get('item_type');
        $itemId = $request->get('item_id');
        
        return view('inventory/reservations/edit')
            ->with('item', new Reservation())
            ->with('item_type', 'create')
            ->with('preset_item_type', $itemType)
            ->with('preset_item_id', $itemId);
    }

    public function store(Request $request): RedirectResponse
    {
        $reservation = new Reservation();
        $reservation->fill($request->all());
        $reservation->reservation_number = Reservation::generateReservationNumber();
        $reservation->status = Reservation::STATUS_ACTIVE;
        $reservation->created_by = auth()->id();
        $reservation->updated_by = auth()->id();

        if ($reservation->save()) {
            return redirect()->route('inventory.reservations.show', $reservation)
                ->with('success', trans('admin/inventory/reservations/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($reservation->getErrors());
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['item', 'reserver', 'department']);
        
        return view('inventory/reservations/view')
            ->with('reservation', $reservation);
    }

    public function edit(Reservation $reservation): View
    {
        return view('inventory/reservations/edit')
            ->with('item', $reservation)
            ->with('item_type', 'edit');
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            return redirect()->route('inventory.reservations.show', $reservation)
                ->with('error', trans('admin/inventory/reservations/message.error.not_editable'));
        }

        $reservation->fill($request->all());
        $reservation->updated_by = auth()->id();

        if ($reservation->save()) {
            return redirect()->route('inventory.reservations.show', $reservation)
                ->with('success', trans('admin/inventory/reservations/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($reservation->getErrors());
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            return redirect()->route('inventory.reservations.index')
                ->with('error', trans('admin/inventory/reservations/message.error.cannot_delete'));
        }

        $reservation->delete();
        
        return redirect()->route('inventory.reservations.index')
            ->with('success', trans('admin/inventory/reservations/message.delete.success'));
    }

    public function fulfill(Request $request, Reservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/reservations/message.error.cannot_fulfill'));
        }

        if ($this->reservationService->fulfillReservation($reservation)) {
            return redirect()->route('inventory.reservations.show', $reservation)
                ->with('success', trans('admin/inventory/reservations/message.success.fulfilled'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to fulfill reservation']);
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/reservations/message.error.cannot_cancel'));
        }

        if ($this->reservationService->cancelReservation($reservation)) {
            return redirect()->route('inventory.reservations.show', $reservation)
                ->with('success', trans('admin/inventory/reservations/message.success.cancelled'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to cancel reservation']);
    }

    public function availability(Request $request, string $itemType, int $itemId): View
    {
        $available = $this->reservationService->getAvailableQuantity($itemType, $itemId);
        $reserved = $this->reservationService->getReservedQuantity($itemType, $itemId);
        $activeReservations = $this->reservationService->getActiveReservations($itemType, $itemId);
        
        return view('inventory/reservations/availability')
            ->with('item_type', $itemType)
            ->with('item_id', $itemId)
            ->with('available_quantity', $available)
            ->with('reserved_quantity', $reserved)
            ->with('active_reservations', $activeReservations);
    }
}
