<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use App\Services\Inventory\ReorderPointEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReorderPointController extends Controller
{
    protected ReorderPointEngine $engine;

    public function __construct(ReorderPointEngine $engine)
    {
        $this->engine = $engine;
    }

    public function index(Request $request): JsonResponse
    {
        $reorderPoints = ReorderPoint::with(['category', 'preferredSupplier', 'creator']);

        if ($request->has('item_type')) {
            $reorderPoints->where('item_type', $request->input('item_type'));
        }

        if ($request->has('category_id')) {
            $reorderPoints->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_active')) {
            $reorderPoints->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'total' => $reorderPoints->count(),
            'rows' => $reorderPoints->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|max:50',
            'item_id' => 'required|integer',
            'category_id' => 'nullable|integer|exists:categories,id',
            'reorder_point' => 'required|integer|min:0',
            'safety_stock' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'preferred_supplier_id' => 'nullable|integer|exists:suppliers,id',
            'auto_replenish' => 'nullable|boolean',
            'lead_time_days' => 'nullable|integer|min:1',
            'alert_threshold_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $reorderPoint = ReorderPoint::create([
            'item_type' => $request->input('item_type'),
            'item_id' => $request->input('item_id'),
            'category_id' => $request->input('category_id'),
            'reorder_point' => $request->input('reorder_point'),
            'safety_stock' => $request->input('safety_stock', 0),
            'reorder_quantity' => $request->input('reorder_quantity', 0),
            'preferred_supplier_id' => $request->input('preferred_supplier_id'),
            'auto_replenish' => $request->boolean('auto_replenish', false),
            'lead_time_days' => $request->input('lead_time_days', 7),
            'alert_threshold_days' => $request->input('alert_threshold_days', 3),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'messages' => 'Reorder point created successfully',
            'reorder_point' => $reorderPoint->load(['category', 'preferredSupplier']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $reorderPoint = ReorderPoint::with(['category', 'preferredSupplier', 'creator', 'updater'])->find($id);

        if (!$reorderPoint) {
            return response()->json(['messages' => 'Reorder point not found'], 404);
        }

        return response()->json($reorderPoint);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reorderPoint = ReorderPoint::find($id);

        if (!$reorderPoint) {
            return response()->json(['messages' => 'Reorder point not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reorder_point' => 'sometimes|integer|min:0',
            'safety_stock' => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:0',
            'preferred_supplier_id' => 'nullable|integer|exists:suppliers,id',
            'auto_replenish' => 'nullable|boolean',
            'lead_time_days' => 'sometimes|integer|min:1',
            'alert_threshold_days' => 'sometimes|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $reorderPoint->update($request->only([
            'reorder_point', 'safety_stock', 'reorder_quantity',
            'preferred_supplier_id', 'auto_replenish', 'lead_time_days',
            'alert_threshold_days', 'is_active'
        ]));
        $reorderPoint->updated_by = auth()->id();
        $reorderPoint->save();

        return response()->json([
            'messages' => 'Reorder point updated successfully',
            'reorder_point' => $reorderPoint->load(['category', 'preferredSupplier']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reorderPoint = ReorderPoint::find($id);

        if (!$reorderPoint) {
            return response()->json(['messages' => 'Reorder point not found'], 404);
        }

        $reorderPoint->delete();

        return response()->json(['messages' => 'Reorder point deleted successfully']);
    }

    public function checkReorderPoints(): JsonResponse
    {
        $items = $this->engine->checkReorderPoints();

        return response()->json([
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function report(): JsonResponse
    {
        $report = $this->engine->getReorderReport();

        return response()->json($report);
    }
}
