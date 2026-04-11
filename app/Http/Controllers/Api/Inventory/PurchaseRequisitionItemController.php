<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisitionItem;
use App\Services\Inventory\RequisitionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseRequisitionItemController extends Controller
{
    protected RequisitionWorkflowService $workflowService;

    public function __construct(RequisitionWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function store(Request $request, int $prId): JsonResponse
    {
        $pr = PurchaseRequisition::find($prId);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$pr->isDraft()) {
            return response()->json(['messages' => 'Items can only be added to draft requisitions'], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'item_type' => 'nullable|string|max:50',
            'item_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'justification' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $item = $this->workflowService->addItem($pr, $request->all());

        return response()->json([
            'messages' => 'Item added to requisition successfully',
            'item' => $item->load(['category', 'manufacturer', 'supplier']),
        ], 201);
    }

    public function update(Request $request, int $prId, int $itemId): JsonResponse
    {
        $pr = PurchaseRequisition::find($prId);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$pr->isDraft()) {
            return response()->json(['messages' => 'Items can only be updated in draft requisitions'], 422);
        }

        $item = PurchaseRequisitionItem::where('pr_id', $prId)->find($itemId);

        if (!$item) {
            return response()->json(['messages' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
            'justification' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $item = $this->workflowService->updateItem($item, $request->all());

        return response()->json([
            'messages' => 'Item updated successfully',
            'item' => $item->load(['category', 'manufacturer', 'supplier']),
        ]);
    }

    public function destroy(int $prId, int $itemId): JsonResponse
    {
        $pr = PurchaseRequisition::find($prId);

        if (!$pr) {
            return response()->json(['messages' => 'Purchase requisition not found'], 404);
        }

        if (!$pr->isDraft()) {
            return response()->json(['messages' => 'Items can only be removed from draft requisitions'], 422);
        }

        $item = PurchaseRequisitionItem::where('pr_id', $prId)->find($itemId);

        if (!$item) {
            return response()->json(['messages' => 'Item not found'], 404);
        }

        $this->workflowService->removeItem($item);

        return response()->json(['messages' => 'Item removed from requisition successfully']);
    }
}
