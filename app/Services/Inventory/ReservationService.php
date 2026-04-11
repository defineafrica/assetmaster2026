<?php

namespace App\Services\Inventory;

use App\Models\Accessory;
use App\Models\Consumable;
use App\Models\Inventory\Reservation\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    public function createReservation(array $data): Reservation
    {
        $reservation = new Reservation();
        $reservation->reservation_number = Reservation::generateReservationNumber();
        $reservation->item_type = $data['item_type'];
        $reservation->item_id = $data['item_id'];
        $reservation->quantity = $data['quantity'] ?? 1;
        $reservation->reserved_by = $data['reserved_by'] ?? auth()->id();
        $reservation->department_id = $data['department_id'] ?? null;
        $reservation->project_code = $data['project_code'] ?? null;
        $reservation->notes = $data['notes'] ?? null;
        $reservation->status = Reservation::STATUS_ACTIVE;
        $reservation->expires_at = $data['expires_at'] ?? now()->addDays(7);
        $reservation->created_by = auth()->id();
        $reservation->save();

        return $reservation;
    }

    public function fulfillReservation(Reservation $reservation): bool
    {
        if (!$reservation->isActive()) {
            return false;
        }

        $availableQty = $this->getAvailableQuantity($reservation->item_type, $reservation->item_id);

        if ($availableQty < $reservation->quantity) {
            Log::warning('Insufficient quantity to fulfill reservation', [
                'reservation_id' => $reservation->id,
                'requested' => $reservation->quantity,
                'available' => $availableQty,
            ]);
            return false;
        }

        DB::beginTransaction();
        try {
            $reservation->status = Reservation::STATUS_FULFILLED;
            $reservation->fulfilled_at = now();
            $reservation->save();

            $this->decrementItemQuantity($reservation->item_type, $reservation->item_id, $reservation->quantity);

            DB::commit();
            Log::info('Reservation fulfilled successfully', ['reservation_id' => $reservation->id]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to fulfill reservation', ['reservation_id' => $reservation->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function cancelReservation(Reservation $reservation): bool
    {
        if (!in_array($reservation->status, [Reservation::STATUS_ACTIVE, Reservation::STATUS_EXPIRED])) {
            return false;
        }

        $reservation->status = Reservation::STATUS_CANCELLED;
        $reservation->cancelled_at = now();
        
        return $reservation->save();
    }

    public function releaseExpiredReservations(): int
    {
        $expiredReservations = Reservation::where('status', Reservation::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            if ($reservation->markExpired()) {
                $count++;
                Log::info('Reservation expired', ['reservation_id' => $reservation->id]);
            }
        }

        return $count;
    }

    public function getAvailableQuantity(string $itemType, int $itemId): int
    {
        $totalQuantity = $this->getTotalQuantity($itemType, $itemId);
        $reservedQuantity = Reservation::getTotalReservedQuantity($itemType, $itemId);

        return max(0, $totalQuantity - $reservedQuantity);
    }

    public function getReservedQuantity(string $itemType, int $itemId): int
    {
        return Reservation::getTotalReservedQuantity($itemType, $itemId);
    }

    protected function getTotalQuantity(string $itemType, int $itemId): int
    {
        if ($itemType === 'consumables') {
            $item = Consumable::find($itemId);
            return $item?->qty ?? 0;
        }

        if ($itemType === 'accessories') {
            $item = Accessory::find($itemId);
            return $item?->qty ?? 0;
        }

        return 0;
    }

    protected function decrementItemQuantity(string $itemType, int $itemId, int $quantity): void
    {
        if ($itemType === 'consumables') {
            Consumable::where('id', $itemId)->decrement('qty', $quantity);
        } elseif ($itemType === 'accessories') {
            Accessory::where('id', $itemId)->decrement('qty', $quantity);
        }
    }

    public function getItemAvailability(string $itemType, int $itemId): array
    {
        $total = $this->getTotalQuantity($itemType, $itemId);
        $reserved = $this->getReservedQuantity($itemType, $itemId);
        $available = $total - $reserved;

        return [
            'item_type' => $itemType,
            'item_id' => $itemId,
            'total_quantity' => $total,
            'reserved_quantity' => $reserved,
            'available_quantity' => $available,
            'active_reservations' => Reservation::getActiveReservationsForItem($itemType, $itemId)->count(),
        ];
    }
}
