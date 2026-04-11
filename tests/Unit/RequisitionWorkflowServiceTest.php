<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Department;
use App\Services\Inventory\RequisitionWorkflowService;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisitionItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RequisitionWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RequisitionWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowService = new RequisitionWorkflowService();
    }

    public function testCanCreatePurchaseRequisition()
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $pr = new PurchaseRequisition([
            'pr_number' => 'PR-20260411-0001',
            'requesting_user_id' => $user->id,
            'department_id' => $department->id,
            'status' => PurchaseRequisition::STATUS_DRAFT,
            'justification' => 'Need for project',
        ]);
        $pr->created_by = $user->id;
        $pr->save();

        $this->assertDatabaseHas('purchase_requisitions', [
            'pr_number' => 'PR-20260411-0001',
            'status' => PurchaseRequisition::STATUS_DRAFT,
        ]);
    }

    public function testRequisitionCanBeSubmitted()
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $pr = new PurchaseRequisition([
            'pr_number' => 'PR-20260411-0002',
            'requesting_user_id' => $user->id,
            'department_id' => $department->id,
            'status' => PurchaseRequisition::STATUS_DRAFT,
        ]);
        $pr->created_by = $user->id;
        $pr->save();

        $item = new PurchaseRequisitionItem([
            'pr_id' => $pr->id,
            'item_name' => 'Test Item',
            'quantity' => 5,
            'unit_cost' => 100,
        ]);
        $item->created_by = $user->id;
        $item->save();

        $result = $this->workflowService->submitForApproval($pr);

        $this->assertTrue($result);
        $pr->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_SUBMITTED, $pr->status);
    }

    public function testRequisitionCanBeApproved()
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $department = Department::factory()->create();

        $pr = new PurchaseRequisition([
            'pr_number' => 'PR-20260411-0003',
            'requesting_user_id' => $user->id,
            'department_id' => $department->id,
            'status' => PurchaseRequisition::STATUS_SUBMITTED,
        ]);
        $pr->created_by = $user->id;
        $pr->save();

        $result = $this->workflowService->approve($pr, $approver, 'Approved for purchase');

        $this->assertTrue($result);
        $pr->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_APPROVED, $pr->status);
        $this->assertEquals($approver->id, $pr->approved_by);
    }

    public function testRequisitionCanBeRejected()
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $department = Department::factory()->create();

        $pr = new PurchaseRequisition([
            'pr_number' => 'PR-20260411-0004',
            'requesting_user_id' => $user->id,
            'department_id' => $department->id,
            'status' => PurchaseRequisition::STATUS_SUBMITTED,
        ]);
        $pr->created_by = $user->id;
        $pr->save();

        $result = $this->workflowService->reject($pr, $approver, 'Budget constraints');

        $this->assertTrue($result);
        $pr->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_REJECTED, $pr->status);
        $this->assertEquals($approver->id, $pr->rejected_by);
        $this->assertEquals('Budget constraints', $pr->rejection_reason);
    }

    public function testRequisitionGeneratesCorrectNumber()
    {
        $prNumber = PurchaseRequisition::generatePrNumber();

        $this->assertStringStartsWith('PR-', $prNumber);
        $this->assertMatchesRegularExpression('/^PR-\d{8}-\d{4}$/', $prNumber);
    }

    public function testRequisitionItemCalculatesTotal()
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $pr = new PurchaseRequisition([
            'pr_number' => 'PR-20260411-0005',
            'requesting_user_id' => $user->id,
            'department_id' => $department->id,
            'status' => PurchaseRequisition::STATUS_DRAFT,
        ]);
        $pr->created_by = $user->id;
        $pr->save();

        $item = new PurchaseRequisitionItem([
            'pr_id' => $pr->id,
            'item_name' => 'Test Item',
            'quantity' => 10,
            'unit_cost' => 50.25,
        ]);
        $item->created_by = $user->id;
        $item->save();

        $this->assertEquals(502.50, $item->total_cost);
    }
}
