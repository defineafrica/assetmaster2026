<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Services\Inventory\GrnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrnController extends Controller
{
    protected GrnService $grnService;

    public function __construct(GrnService $grnService)
    {
        $this->grnService = $grnService;
    }

    public function index(Request $request): JsonResponse
    {
        $grns = GoodsReceivedNote::with(['supplier', 'receiver', 'approver', 'creator'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $grns->where('status', $request->input('status'));
        }

        if ($request->has('supplier_id')) {
            $grns->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('received_date_from')) {
            $grns->where('received_date', '>=', $request->input('received_date_from'));
        }

        if ($request->has('received_date_to')) {
            $grns->where('received_date', '<=', $request->input('received_date_to'));
        }

        return response()->json([
            'total' => $grns->count(),
            'rows' => $grns->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'received_by' => 'nullable|integer|exists:users,id',
            'received_date' => 'nullable|date',
            'purchase_order_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $grn = $this->grnService->createGrn($request->all());

        return response()->json([
            'messages' => 'GRN created successfully',
            'grn' => $grn->load(['supplier', 'receiver']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::with([
            'supplier',
            'receiver',
            'approver',
            'creator',
            'items.category',
            'items.manufacturer',
            'inspections.inspector',
        ])->find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        return response()->json([
            'grn' => $grn,
            'summary' => $this->grnService->getGrnSummary($grn),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isEditable()) {
            return response()->json(['messages' => 'GRN cannot be edited in current status'], 422);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'received_date' => 'nullable|date',
            'purchase_order_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->errors()], 400);
        }

        $grn->update($request->only([
            'supplier_id', 'received_date', 'purchase_order_number', 'notes'
        ]));
        $grn->updated_by = auth()->id();
        $grn->save();

        return response()->json([
            'messages' => 'GRN updated successfully',
            'grn' => $grn->load(['supplier', 'receiver']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isEditable()) {
            return response()->json(['messages' => 'GRN cannot be deleted in current status'], 422);
        }

        $grn->delete();

        return response()->json(['messages' => 'GRN deleted successfully']);
    }

    public function post(int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$this->grnService->postGrn($grn)) {
            return response()->json(['messages' => 'Failed to post GRN. Ensure it has items.'], 422);
        }

        return response()->json([
            'messages' => 'GRN posted successfully',
            'grn' => $grn->load(['supplier', 'receiver']),
        ]);
    }

    public function lock(int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if (!$grn->isLockable()) {
            return response()->json(['messages' => 'GRN cannot be locked in current status'], 422);
        }

        $grn->lock();

        return response()->json([
            'messages' => 'GRN locked successfully',
            'grn' => $grn,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $grn = GoodsReceivedNote::find($id);

        if (!$grn) {
            return response()->json(['messages' => 'GRN not found'], 404);
        }

        if ($grn->status !== GoodsReceivedNote::STATUS_POSTED) {
            return response()->json(['messages' => 'Only posted GRNs can be approved'], 422);
        }

        $grn->approved_by = auth()->id();
        $grn->approved_at = now();
        $grn->status = GoodsReceivedNote::STATUS_LOCKED;
        $grn->save();

        return response()->json([
            'messages' => 'GRN approved successfully',
            'grn' => $grn->load(['supplier', 'receiver', 'approver']),
        ]);
    }
}
