<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Services\Inventory\RequisitionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseRequisitionController extends Controller
{
    protected RequisitionWorkflowService $workflowService;

    public function __construct(RequisitionWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index(Request $request): JsonResponse
    {
        $requisitions = PurchaseRequisition::with(['requestingUser', 'department', 'approver']);

        if ($request->has('status')) {
            $requisitions->where('status', $request->input('status'));
        }

        if ($request->has('requesting_user_id')) {
            $requisitions->where('requesting_user_id', $request->input('requesting_user_id'));
        }

        if ($request->has('department_id')) {
            $requisitions->where('department_id', $request->input('department_id'));
        }

        return response()->json([
            'total' => $requisitions->count(),
            'rows' => $requisitions->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'requesting_user_id' => 'nullable|integer|exists:users,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'notes' => 'nullable|string',
            'justification' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $pr = $this->workflowService->createRequisition($request->all());

        return response()->json([
            'messages' => 'Purchase requisition created successfully',
            'requisition' => $pr->load(['requestingUser', 'department']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::with([
            'requestingUser',
            'department',
            'approver',
            'rejector',
            'items.category',
            'items.manufacturer',
            'items.supplier',
        ])->find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        return response()->json([
            'requisition' => $pr,
            'summary' => $this->workflowService->getRequisitionSummary($pr),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$pr->isDraft()) {
            return response()->json(['messages' => 'Only draft requisitions can be updated'], 422);
        }

        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|integer|exists:departments,id',
            'notes' => 'nullable|string',
            'justification' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $pr->update($request->only(['department_id', 'notes', 'justification']));
        $pr->updated_by = auth()->id();
        $pr->save();

        return response()->json([
            'messages' => 'Purchase requisition updated successfully',
            'requisition' => $pr->load(['requestingUser', 'department']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$pr->isDraft()) {
            return response()->json(['messages' => 'Only draft requisitions can be deleted'], 422);
        }

        $pr->delete();

        return response()->json(['messages' => 'Purchase requisition deleted successfully']);
    }

    public function submit(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$this->workflowService->submit($pr)) {
            return response()->json(['messages' => 'Failed to submit requisition'], 422);
        }

        return response()->json([
            'messages' => 'Purchase requisition submitted successfully',
            'requisition' => $pr->fresh()->load(['requestingUser', 'department']),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        $comments = $request->input('comments');

        if (!$this->workflowService->approve($pr, auth()->user(), $comments)) {
            return response()->json(['messages' => 'Failed to approve requisition'], 422);
        }

        return response()->json([
            'messages' => 'Purchase requisition approved successfully',
            'requisition' => $pr->fresh()->load(['requestingUser', 'department', 'approver']),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        if (!$this->workflowService->reject($pr, auth()->user(), $request->input('reason'))) {
            return response()->json(['messages' => 'Failed to reject requisition'], 422);
        }

        return response()->json([
            'messages' => 'Purchase requisition rejected',
            'requisition' => $pr->fresh()->load(['requestingUser', 'department', 'rejector']),
        ]);
    }

    public function fulfill(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::find($id);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$this->workflowService->fulfill($pr)) {
            return response()->json(['messages' => 'Failed to fulfill requisition'], 422);
        }

        return response()->json([
            'messages' => 'Purchase requisition fulfilled',
            'requisition' => $pr->fresh()->load(['requestingUser', 'department']),
        ]);
    }
}
