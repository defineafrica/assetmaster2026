<?php

namespace Tests\Unit;

use App\Models\Consumable;
use App\Models\User;
use App\Services\Inventory\GrnService;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GrnServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GrnService $grnService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grnService = new GrnService();
    }

    public function testCanCreateGrn()
    {
        $user = User::factory()->create();
        $supplier = \App\Models\Supplier::factory()->create();

        $grn = new GoodsReceivedNote([
            'grn_number' => 'GRN-20260411-0001',
            'supplier_id' => $supplier->id,
            'received_by' => $user->id,
            'received_date' => now()->format('Y-m-d'),
            'status' => GoodsReceivedNote::STATUS_DRAFT,
        ]);
        $grn->created_by = $user->id;
        $grn->save();

        $this->assertDatabaseHas('goods_received_notes', [
            'grn_number' => 'GRN-20260411-0001',
            'supplier_id' => $supplier->id,
            'status' => GoodsReceivedNote::STATUS_DRAFT,
        ]);
    }

    public function testGrnCanBePosted()
    {
        $user = User::factory()->create();
        $supplier = \App\Models\Supplier::factory()->create();

        $grn = new GoodsReceivedNote([
            'grn_number' => 'GRN-20260411-0002',
            'supplier_id' => $supplier->id,
            'received_by' => $user->id,
            'received_date' => now()->format('Y-m-d'),
            'status' => GoodsReceivedNote::STATUS_DRAFT,
        ]);
        $grn->created_by = $user->id;
        $grn->save();

        $result = $this->grnService->postGrn($grn);

        $this->assertTrue($result);
        $grn->refresh();
        $this->assertEquals(GoodsReceivedNote::STATUS_POSTED, $grn->status);
    }

    public function testGrnCanBeLocked()
    {
        $user = User::factory()->create();
        $supplier = \App\Models\Supplier::factory()->create();

        $grn = new GoodsReceivedNote([
            'grn_number' => 'GRN-20260411-0003',
            'supplier_id' => $supplier->id,
            'received_by' => $user->id,
            'received_date' => now()->format('Y-m-d'),
            'status' => GoodsReceivedNote::STATUS_POSTED,
        ]);
        $grn->created_by = $user->id;
        $grn->save();

        $result = $this->grnService->lockGrn($grn);

        $this->assertTrue($result);
        $grn->refresh();
        $this->assertEquals(GoodsReceivedNote::STATUS_LOCKED, $grn->status);
    }

    public function testGrnGeneratesCorrectNumber()
    {
        $grnNumber = GoodsReceivedNote::generateGrnNumber();

        $this->assertStringStartsWith('GRN-', $grnNumber);
        $this->assertMatchesRegularExpression('/^GRN-\d{8}-\d{4}$/', $grnNumber);
    }

    public function testGrnItemCalculatesTotalCost()
    {
        $user = User::factory()->create();
        $supplier = \App\Models\Supplier::factory()->create();

        $grn = new GoodsReceivedNote([
            'grn_number' => 'GRN-20260411-0004',
            'supplier_id' => $supplier->id,
            'received_by' => $user->id,
            'received_date' => now()->format('Y-m-d'),
            'status' => GoodsReceivedNote::STATUS_DRAFT,
        ]);
        $grn->created_by = $user->id;
        $grn->save();

        $item = new GrnItem([
            'grn_id' => $grn->id,
            'item_name' => 'Test Item',
            'quantity' => 10,
            'unit_cost' => 25.50,
        ]);
        $item->created_by = $user->id;
        $item->save();

        $this->assertEquals(255.00, $item->total_cost);
    }
}
