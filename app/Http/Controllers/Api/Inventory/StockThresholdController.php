<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockThreshold\StockThreshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockThresholdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $thresholds = StockThreshold::with(['creator', 'updater'])
            ->orderBy('item_type')
            ->orderBy('item_id');

        if ($request->has('item_type')) {
            $thresholds->where('item_type', $request->input('item_type'));
        }

        if ($request->has('is_active')) {
            $thresholds->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'total' => $thresholds->count(),
            'rows' => $thresholds->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|max:50',
            'item_id' => 'required|integer',
            'min_quantity' => 'required|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'alert_email' => 'nullable|email',
            'send_sms' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors(),
            ], 400);
        }

        $threshold = StockThreshold::create([
            'item_type' => $request->input('item_type'),
            'item_id' => $request->input('item_id'),
            'min_quantity' => $request->input('min_quantity'),
            'reorder_quantity' => $request->input('reorder_quantity', 0),
            'alert_email' => $request->input('alert_email'),
            'send_sms' => $request->boolean('send_sms', false),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'messages' => 'Stock threshold created successfully',
            'threshold' => $threshold,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $threshold = StockThreshold::with(['creator', 'updater', 'item'])->find($id);

        if (!$threshold) {
            return response()->json(['messages' => 'Stock threshold not found'], 404);
        }

        return response()->json($threshold);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $threshold = StockThreshold::find($id);

        if (!$threshold) {
            return response()->json(['messages' => 'Stock threshold not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'min_quantity' => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:0',
            'alert_email' => 'nullable|email',
            'send_sms' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors(),
            ], 400);
        }

        $threshold->update($request->only([
            'min_quantity', 'reorder_quantity', 'alert_email', 'send_sms', 'is_active'
        ]));
        $threshold->updated_by = auth()->id();
        $threshold->save();

        return response()->json([
            'messages' => 'Stock threshold updated successfully',
            'threshold' => $threshold,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $threshold = StockThreshold::find($id);

        if (!$threshold) {
            return response()->json(['messages' => 'Stock threshold not found'], 404);
        }

        $threshold->delete();

        return response()->json([
            'messages' => 'Stock threshold deleted successfully',
        ]);
    }

    public function checkThreshold(string $itemType, int $itemId): JsonResponse
    {
        $threshold = StockThreshold::getThresholdForItem($itemType, $itemId);

        if (!$threshold) {
            return response()->json([
                'has_threshold' => false,
                'message' => 'No threshold configured for this item',
            ]);
        }

        $currentQty = $this->getCurrentQuantity($itemType, $itemId);
        $belowThreshold = $currentQty !== null && $currentQty <= $threshold->min_quantity;

        return response()->json([
            'has_threshold' => true,
            'below_threshold' => $belowThreshold,
            'current_quantity' => $currentQty,
            'min_quantity' => $threshold->min_quantity,
            'reorder_quantity' => $threshold->reorder_quantity,
            'alert_email' => $threshold->alert_email,
        ]);
    }

    protected function getCurrentQuantity(string $itemType, int $itemId): ?int
    {
        if ($itemType === 'consumables') {
            $item = \App\Models\Consumable::find($itemId);
            return $item?->qty;
        }

        if ($itemType === 'accessories') {
            $item = \App\Models\Accessory::find($itemId);
            return $item?->qty;
        }

        return null;
    }
}
