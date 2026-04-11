<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\Department;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequisitionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->createUsers()->create();
    }

    public function testCannotAccessRequisitionsWithoutPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.inventory.requisitions.index'))
            ->assertForbidden();
    }

    public function testCanListRequisitions()
    {
        $department = Department::factory()->create();
        
        $pr = PurchaseRequisition::factory()->create([
            'requesting_user_id' => $this->user->id,
            'department_id' => $department->id,
            'status' => 'draft',
        ]);

        $this->actingAsForApi($this->user)
            ->getJson(route('api.inventory.requisitions.index'))
            ->assertOk()
            ->assertJsonStructure([
                'rows' => [
                    '*' => [
                        'id',
                        'pr_number',
                        'requesting_user_id',
                        'department_id',
                        'status',
                    ]
                ]
            ]);
    }

    public function testCanCreateRequisition()
    {
        $department = Department::factory()->create();

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.requisitions.store'), [
                'department_id' => $department->id,
                'justification' => 'Test justification',
                'notes' => 'Test notes',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $this->assertDatabaseHas('purchase_requisitions', [
            'requesting_user_id' => $this->user->id,
            'department_id' => $department->id,
            'status' => 'draft',
        ]);
    }

    public function testCanSubmitRequisition()
    {
        $department = Department::factory()->create();
        
        $pr = PurchaseRequisition::factory()->create([
            'requesting_user_id' => $this->user->id,
            'department_id' => $department->id,
            'status' => 'draft',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.requisitions.submit', $pr))
            ->assertStatusMessageIs('success')
            ->assertOk();

        $pr->refresh();
        $this->assertEquals('submitted', $pr->status);
    }

    public function testCanApproveRequisition()
    {
        $department = Department::factory()->create();
        
        $pr = PurchaseRequisition::factory()->create([
            'requesting_user_id' => $this->user->id,
            'department_id' => $department->id,
            'status' => 'submitted',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.requisitions.approve', $pr), [
                'comments' => 'Approved',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $pr->refresh();
        $this->assertEquals('approved', $pr->status);
    }

    public function testCanRejectRequisition()
    {
        $department = Department::factory()->create();
        
        $pr = PurchaseRequisition::factory()->create([
            'requesting_user_id' => $this->user->id,
            'department_id' => $department->id,
            'status' => 'submitted',
        ]);

        $this->actingAsForApi($this->user)
            ->postJson(route('api.inventory.requisitions.reject', $pr), [
                'rejection_reason' => 'Budget constraints',
            ])
            ->assertStatusMessageIs('success')
            ->assertOk();

        $pr->refresh();
        $this->assertEquals('rejected', $pr->status);
        $this->assertEquals('Budget constraints', $pr->rejection_reason);
    }
}
