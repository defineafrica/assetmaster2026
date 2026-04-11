<?php

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Services\Inventory\GrnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrnItemController extends BaseInventoryController
{
    protected GrnService $grnService;

    public function __construct(GrnService $grnService)
    {
        $this->grnService = $grnService;
        parent::__construct();
    }

    public function store(Request $request, int $grnId)
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return redirect()->route('inventory.grn.index')
                ->with('error', 'GRN not found.');
        }

        if (!$grn->isEditable()) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'Items cannot be added to this GRN in its current status.');
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'item_type' => 'nullable|string|max:50',
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->grnService->addItem($grn, $request->all());

        return redirect()->route('inventory.grn.show', $grn->id)
            ->with('success', 'Item added to GRN successfully.');
    }

    public function update(Request $request, int $grnId, int $itemId)
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return redirect()->route('inventory.grn.index')
                ->with('error', 'GRN not found.');
        }

        if (!$grn->isEditable()) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'Items cannot be updated in this GRN in its current status.');
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'GRN item not found.');
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|string|max:255',
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->grnService->updateItem($item, $request->all());

        return redirect()->route('inventory.grn.show', $grn->id)
            ->with('success', 'GRN item updated successfully.');
    }

    public function destroy(int $grnId, int $itemId)
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return redirect()->route('inventory.grn.index')
                ->with('error', 'GRN not found.');
        }

        if (!$grn->isEditable()) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'Items cannot be removed from this GRN in its current status.');
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'GRN item not found.');
        }

        $item->delete();

        return redirect()->route('inventory.grn.show', $grn->id)
            ->with('success', 'GRN item removed successfully.');
    }

    public function inspect(Request $request, int $grnId, int $itemId)
    {
        $grn = GoodsReceivedNote::find($grnId);

        if (!$grn) {
            return redirect()->route('inventory.grn.index')
                ->with('error', 'GRN not found.');
        }

        $item = GrnItem::where('grn_id', $grnId)->find($itemId);

        if (!$item) {
            return redirect()->route('inventory.grn.show', $grn->id)
                ->with('error', 'GRN item not found.');
        }

        $validator = Validator::make($request->all(), [
            'result' => 'required|in:pending,accepted,rejected,partial',
            'accepted_quantity' => 'nullable|integer|min:0',
            'rejected_quantity' => 'nullable|integer|min:0',
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'discrepancy_type' => 'nullable|string|max:100',
            'discrepancy_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->grnService->recordInspection($item, $request->all());

        return redirect()->route('inventory.grn.show', $grn->id)
            ->with('success', 'Inspection recorded successfully.');
    }
}
