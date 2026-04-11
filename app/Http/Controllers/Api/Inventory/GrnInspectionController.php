<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnInspection;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Services\Inventory\GrnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrnInspectionController extends Controller
{
    protected GrnService $grnService;

    public function __construct(GrnService $grnService)
    {
        $this->grnService = $grnService;
    }

    public function inspect(Request $request, int $grnId, int $itemId): JsonResponse
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return response()->json(['messages' => 'GRN item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'inspection_date' => 'nullable|date',
            'result' => 'required|in:pending,accepted,rejected,partial',
            'accepted_quantity' => 'nullable|integer|min:0',
            'rejected_quantity' => 'nullable|integer|min:0',
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'discrepancy_type' => 'nullable|string|max:100',
            'discrepancy_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $inspection = $this->grnService->recordInspection($item, $request->all());

        return response()->json([
            'messages' => 'Inspection recorded successfully',
            'inspection' => $inspection->load(['inspector']),
            'item' => $item->fresh()->load(['category', 'manufacturer']),
        ]);
    }
}
