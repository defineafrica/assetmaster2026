<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Services\Inventory\GrnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrnItemController extends Controller
{
    protected GrnService $grnService;

    public function __construct(GrnService $grnService)
    {
        $this->grnService = $grnService;
    }

    public function store(Request $request, int $grnId): JsonResponse
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isEditable()) {
            return response()->json(['messages' => 'GRN cannot be modified in current status'], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'item_type' => 'nullable|string|max:50',
            'item_description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'expected_quantity' => 'nullable|integer|min:1',
            'received_quantity' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|max:100',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $item = $this->grnService->addItem($grn, $request->all());

        return response()->json([
            'messages' => 'GRN item added successfully',
            'item' => $item->load(['category', 'manufacturer']),
        ], 201);
    }

    public function update(Request $request, int $grnId, int $itemId): JsonResponse
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isEditable()) {
            return response()->json(['messages' => 'GRN cannot be modified in current status'], 422);
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return response()->json(['messages' => 'GRN item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|string|max:255',
            'item_description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'expected_quantity' => 'sometimes|integer|min:1',
            'received_quantity' => 'sometimes|integer|min:0',
            'accepted_quantity' => 'sometimes|integer|min:0',
            'rejected_quantity' => 'sometimes|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|max:100',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $item = $this->grnService->updateItem($item, $request->all());

        return response()->json([
            'messages' => 'GRN item updated successfully',
            'item' => $item->load(['category', 'manufacturer']),
        ]);
    }

    public function destroy(int $grnId, int $itemId): JsonResponse
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isEditable()) {
            return response()->json(['messages' => 'GRN cannot be modified in current status'], 422);
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return response()->json(['messages' => 'GRN item not found'], 404);
        }

        $item->delete();

        return response()->json(['messages' => 'GRN item deleted successfully']);
    }
}
