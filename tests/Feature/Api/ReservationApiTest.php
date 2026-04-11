<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\Inventory\Reservation\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->createUsers()->create();
    }

    public function testCannotAccessReservationsWithoutPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.inventory.reservations.index'))
            ->assertForbidden();
    }

    public function testCanListReservations()
    {
        $consumable = Consumable::factory()->create();
        
        $reservation = Reservation::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.reservations.index'))
            ->assertOk()
            ->assertJsonStructure([
                'rows' => [
                    '*' => [
                        'id',
                        'reservation_number',
                        'item_type',
                        'item_id',
                        'quantity',
                        'status',
                    ]
                ]
            ]);
    }

    public function testCanCreateReservation()
    {
        $consumable = Consumable::factory()->create(['qty' => 100]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.reservations.store'), [
                'item_type' => 'consumable',
                'item_id' => $consumable->id,
                'quantity' => 10,
                'notes' => 'Test reservation',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('reservations', [
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'status' => 'active',
        ]);
    }

    public function testCanCancelReservation()
    {
        $consumable = Consumable::factory()->create();
        
        $reservation = Reservation::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.reservations.cancel', $reservation))
            ->assertStatusMessageIs('success')
            ->assertOk();

        $reservation->refresh();
        $this->assertEquals('cancelled', $reservation->status);
    }

    public function testCanCheckAvailability()
    {
        $consumable = Consumable::factory()->create(['qty' => 100]);

        Reservation::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'quantity' => 30,
            'status' => 'active',
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.reservations.availability', ['consumable', $consumable->id]))
            ->assertOk()
            ->assertJsonStructure([
                'available_quantity',
                'reserved_quantity',
            ]);
    }
}
