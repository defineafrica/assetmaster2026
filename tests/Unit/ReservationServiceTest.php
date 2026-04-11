<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Department;
use App\Services\Inventory\ReservationService;
use App\Models\Inventory\Reservation\Reservation;
use App\Models\Consumable;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = new ReservationService();
    }

    public function testCanCreateReservation()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $reservation = new Reservation([
            'reservation_number' => 'RES-20260411-0001',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
            'expires_at' => now()->addDays(7),
        ]);
        $reservation->created_by = $user->id;
        $reservation->save();

        $this->assertDatabaseHas('reservations', [
            'reservation_number' => 'RES-20260411-0001',
            'quantity' => 10,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
    }

    public function testReservationCanBeFulfilled()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $reservation = new Reservation([
            'reservation_number' => 'RES-20260411-0002',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
        $reservation->created_by = $user->id;
        $reservation->save();

        $result = $this->reservationService->fulfillReservation($reservation);

        $this->assertTrue($result);
        $reservation->refresh();
        $this->assertEquals(Reservation::STATUS_FULFILLED, $reservation->status);
        $this->assertNotNull($reservation->fulfilled_at);
    }

    public function testReservationCanBeCancelled()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $reservation = new Reservation([
            'reservation_number' => 'RES-20260411-0003',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
        $reservation->created_by = $user->id;
        $reservation->save();

        $result = $this->reservationService->cancelReservation($reservation);

        $this->assertTrue($result);
        $reservation->refresh();
        $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
        $this->assertNotNull($reservation->cancelled_at);
    }

    public function testReservationGeneratesCorrectNumber()
    {
        $resNumber = Reservation::generateReservationNumber();

        $this->assertStringStartsWith('RES-', $resNumber);
        $this->assertMatchesRegularExpression('/^RES-\d{8}-\d{4}$/', $resNumber);
    }

    public function testCanGetAvailableQuantity()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $reservation = new Reservation([
            'reservation_number' => 'RES-20260411-0004',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 30,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
        $reservation->created_by = $user->id;
        $reservation->save();

        $available = $this->reservationService->getAvailableQuantity('consumable', $consumable->id);

        $this->assertEquals(70, $available);
    }

    public function testCanGetReservedQuantity()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $reservation1 = new Reservation([
            'reservation_number' => 'RES-20260411-0005',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 20,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
        $reservation1->created_by = $user->id;
        $reservation1->save();

        $reservation2 = new Reservation([
            'reservation_number' => 'RES-20260411-0006',
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 15,
            'reserved_by' => $user->id,
            'status' => Reservation::STATUS_ACTIVE,
        ]);
        $reservation2->created_by = $user->id;
        $reservation2->save();

        $reserved = $this->reservationService->getReservedQuantity('consumable', $consumable->id);

        $this->assertEquals(35, $reserved);
    }
}
