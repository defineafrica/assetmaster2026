<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrnApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->createUsers()->create();
    }

    public function testCannotAccessGrnWithoutPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.inventory.grn.index'))
            ->assertForbidden();
    }

    public function testCanListGrn()
    {
        $supplier = Supplier::factory()->create();
        
        $grn = GoodsReceivedNote::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.grn.index'))
            ->assertOk()
            ->assertJsonStructure([
                'rows' => [
                    '*' => [
                        'id',
                        'grn_number',
                        'supplier_id',
                        'status',
                        'received_date',
                    ]
                ]
            ]);
    }

    public function testCanCreateGrn()
    {
        $supplier = Supplier::factory()->create();

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.grn.store'), [
                'supplier_id' => $supplier->id,
                'received_date' => now()->format('Y-m-d'),
                'purchase_order_number' => 'PO-001',
                'notes' => 'Test GRN',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('goods_received_notes', [
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);
    }

    public function testCanPostGrn()
    {
        $supplier = Supplier::factory()->create();
        
        $grn = GoodsReceivedNote::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.grn.post', $grn))
            ->assertStatusMessageIs('success')
            ->assertOk();

        $grn->refresh();
        $this->assertEquals('posted', $grn->status);
    }

    public function testCanLockGrn()
    {
        $supplier = Supplier::factory()->create();
        
        $grn = GoodsReceivedNote::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'posted',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.grn.lock', $grn))
            ->assertStatusMessageIs('success')
            ->assertOk();

        $grn->refresh();
        $this->assertEquals('locked', $grn->status);
    }
}
