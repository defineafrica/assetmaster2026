<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Inventory\StockThreshold\StockThreshold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockThresholdApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->createUsers()->create();
    }

    public function testCannotAccessStockThresholdsWithoutPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.inventory.stock-thresholds.index'))
            ->assertForbidden();
    }

    public function testCanListStockThresholds()
    {
        $consumable = Consumable::factory()->create();
        
        $threshold = StockThreshold::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.stock-thresholds.index'))
            ->assertOk()
            ->assertJsonStructure([
                'rows' => [
                    '*' => [
                        'id',
                        'item_type',
                        'item_id',
                        'min_quantity',
                        'reorder_quantity',
                    ]
                ]
            ]);
    }

    public function testCanCreateStockThreshold()
    {
        $consumable = Consumable::factory()->create();

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.stock-thresholds.store'), [
                'item_type' => 'consumable',
                'item_id' => $consumable->id,
                'min_quantity' => 10,
                'reorder_quantity' => 50,
                'is_active' => true,
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('stock_thresholds', [
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'min_quantity' => 10,
        ]);
    }

    public function testCanUpdateStockThreshold()
    {
        $consumable = Consumable::factory()->create();
        
        $threshold = StockThreshold::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
        ]);

        $this->actingAsForApi($this->user)
            ->putJson(route('api.inventory.stock-thresholds.update', $threshold), [
                'item_type' => 'consumable',
                'item_id' => $consumable->id,
                'min_quantity' => 15,
                'reorder_quantity' => 75,
                'is_active' => true,
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('stock_thresholds', [
            'id' => $threshold->id,
            'min_quantity' => 15,
            'reorder_quantity' => 75,
        ]);
    }

    public function testCanDeleteStockThreshold()
    {
        $consumable = Consumable::factory()->create();
        
        $threshold = StockThreshold::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
        ]);

        $this->actingAsForApi($this->user)
            ->deleteJson(route('api.inventory.stock-thresholds.destroy', $threshold))
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseMissing('stock_thresholds', [
            'id' => $threshold->id,
        ]);
    }

    public function testCanCheckThreshold()
    {
        $consumable = Consumable::factory()->create(['qty' => 5]);
        
        $threshold = StockThreshold::factory()->create([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.stock-thresholds.check', ['consumable', $consumable->id]))
            ->assertOk()
            ->assertJsonStructure([
                'below_threshold',
                'current_quantity',
                'min_quantity',
            ]);
    }
}
