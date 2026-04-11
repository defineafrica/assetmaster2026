<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Accessory;
use App\Services\Inventory\ReorderPointEngine;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReorderPointEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ReorderPointEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ReorderPointEngine();
    }

    public function testCanCreateReorderPoint()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create();

        $reorderPoint = new ReorderPoint([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'safety_stock' => 5,
            'reorder_quantity' => 50,
            'is_active' => true,
        ]);
        $reorderPoint->created_by = $user->id;
        $reorderPoint->save();

        $this->assertDatabaseHas('reorder_points', [
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'is_active' => true,
        ]);
    }

    public function testCanCalculateSafetyStock()
    {
        $safetyStock = ReorderPoint::calculateSafetyStock(7, 10, 1.65);

        $this->assertIsFloat($safetyStock);
        $this->assertGreaterThan(0, $safetyStock);
    }

    public function testCanGenerateReorderReport()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 15]);

        $reorderPoint = new ReorderPoint([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'safety_stock' => 5,
            'reorder_quantity' => 50,
            'is_active' => true,
        ]);
        $reorderPoint->created_by = $user->id;
        $reorderPoint->save();

        $report = $this->engine->generateReorderReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('items_below_reorder', $report);
        $this->assertArrayHasKey('total_items_tracked', $report);
        $this->assertArrayHasKey('items_needing_reorder_count', $report);
        $this->assertArrayHasKey('total_reorder_value', $report);
    }

    public function testCanCheckReorderPoints()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['qty' => 5]);

        $reorderPoint = new ReorderPoint([
            'item_type' => 'consumable',
            'item_id' => $consumable->id,
            'reorder_point' => 20,
            'safety_stock' => 5,
            'reorder_quantity' => 50,
            'is_active' => true,
        ]);
        $reorderPoint->created_by = $user->id;
        $reorderPoint->save();

        $itemsBelowReorder = $this->engine->checkReorderPoints();

        $this->assertIsArray($itemsBelowReorder);
        $this->assertGreaterThanOrEqual(1, count($itemsBelowReorder));
    }
}
