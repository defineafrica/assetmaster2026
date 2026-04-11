<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderPointApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->createUsers()->create();
    }

    public function testCannotAccessReorderPointsWithoutPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.inventory.reorder-points.index'))
            ->assertForbidden();
    }

    public function testCanListReorderPoints()
    {
        $consumable = Consumable::factory()->create();
        
        $reorderPoint = ReorderPoint::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'reorder_quantity' => 50,
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.reorder-points.index'))
            ->assertOk()
            ->assertJsonStructure([
                'rows' => [
                    '*' => [
                        'id',
                        'item_type',
                        'item_id',
                        'reorder_point',
                        'reorder_quantity',
                        'safety_stock',
                    ]
                ]
            ]);
    }

    public function testCanCreateReorderPoint()
    {
        $consumable = Consumable::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.reorder-points.store'), [
                'item_type' => 'consumable',
                'item_id' => $consumable->id,
                'reorder_point' => 20,
                'reorder_quantity' => 50,
                'safety_stock' => 5,
                'preferred_supplier_id' => $supplier->id,
                'is_active' => true,
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('reorder_points', [
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
        ]);
    }

    public function testCanGetReorderReport()
    {
        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.reorder-points.report'))
            ->assertOk()
            ->assertJsonStructure([
                'items_below_reorder',
                'total_items_tracked',
                'items_needing_reorder_count',
                'total_reorder_value',
            ]);
    }

    public function testCanCheckReorderPoints()
    {
        $consumable = Consumable::factory()->create(['qty' => 5]);
        
        ReorderPoint::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'reorder_quantity' => 50,
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.reorder-points.check'))
            ->assertOk();
    }
}
