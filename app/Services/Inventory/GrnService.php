<?php

namespace App\Services\Inventory;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnInspection;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrnService
{
    public function createGrn(array $data): GoodsReceivedNote
    {
        $grn = new GoodsReceivedNote();
        $grn->grn_number = GoodsReceivedNote::getNextGrnNumber();
        $grn->supplier_id = $data['supplier_id'];
        $grn->received_by = $data['received_by'] ?? auth()->id();
        $grn->received_date = $data['received_date'] ?? now();
        $grn->purchase_order_number = $data['purchase_order_number'] ?? null;
        $grn->status = GoodsReceivedNote::STATUS_DRAFT;
        $grn->notes = $data['notes'] ?? null;
        $grn->created_by = auth()->id();
        $grn->save();

        return $grn;
    }

    public function addItem(GoodsReceivedNote $grn, array $itemData): GrnItem
    {
        $item = new GrnItem();
        $item->grn_id = $grn->id;
        $item->item_type = $itemData['item_type'] ?? 'asset';
        $item->item_name = $itemData['item_name'];
        $item->item_description = $itemData['item_description'] ?? null;
        $item->category_id = $itemData['category_id'] ?? null;
        $item->manufacturer_id = $itemData['manufacturer_id'] ?? null;
        $item->supplier_id = $grn->supplier_id;
        $item->expected_quantity = $itemData['expected_quantity'] ?? 1;
        $item->received_quantity = $itemData['received_quantity'] ?? $itemData['expected_quantity'] ?? 1;
        $item->accepted_quantity = 0;
        $item->rejected_quantity = 0;
        $item->unit_cost = $itemData['unit_cost'] ?? 0;
        $item->serial_number = $itemData['serial_number'] ?? null;
        $item->batch_number = $itemData['batch_number'] ?? null;
        $item->expiry_date = $itemData['expiry_date'] ?? null;
        $item->notes = $itemData['notes'] ?? null;
        $item->created_by = auth()->id();
        $item->calculateTotals();
        $item->save();

        return $item;
    }

    public function updateItem(GrnItem $item, array $data): GrnItem
    {
        $item->fill($data);
        
        if (isset($data['accepted_quantity']) || isset($data['rejected_quantity'])) {
            $item->accepted_quantity = $data['accepted_quantity'] ?? $item->accepted_quantity;
            $item->rejected_quantity = $data['rejected_quantity'] ?? ($item->received_quantity - $item->accepted_quantity);
            $item->calculateTotals();
        }
        
        $item->updated_by = auth()->id();
        $item->save();

        return $item;
    }

    public function recordInspection(GrnItem $item, array $inspectionData): GrnInspection
    {
        $inspection = new GrnInspection();
        $inspection->grn_id = $item->grn_id;
        $inspection->grn_item_id = $item->id;
        $inspection->inspected_by = auth()->id();
        $inspection->inspection_date = $inspectionData['inspection_date'] ?? now();
        $inspection->result = $inspectionData['result'] ?? GrnInspection::RESULT_PENDING;
        $inspection->notes = $inspectionData['notes'] ?? null;
        $inspection->discrepancy_type = $inspectionData['discrepancy_type'] ?? null;
        $inspection->discrepancy_notes = $inspectionData['discrepancy_notes'] ?? null;
        $inspection->created_by = auth()->id();
        $inspection->save();

        if ($inspection->result !== GrnInspection::RESULT_PENDING) {
            $item->accepted_quantity = $inspectionData['accepted_quantity'] ?? 0;
            $item->rejected_quantity = $inspectionData['rejected_quantity'] ?? 0;
            $item->condition = $inspectionData['condition'] ?? GrnItem::CONDITION_GOOD;
            $item->calculateTotals();
            $item->save();
        }

        return $inspection;
    }

    public function postGrn(GoodsReceivedNote $grn): bool
    {
        if (!$grn->isPostable()) {
            return false;
        }

        DB::beginTransaction();
        try {
            foreach ($grn->items as $item) {
                $this->createInventoryFromGrnItem($item);
            }

            $grn->status = GoodsReceivedNote::STATUS_POSTED;
            $grn->save();

            DB::commit();
            Log::info('GRN posted successfully', ['grn_id' => $grn->id]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post GRN', ['grn_id' => $grn->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    protected function createInventoryFromGrnItem(GrnItem $item): ?Asset
    {
        if ($item->accepted_quantity <= 0) {
            return null;
        }

        for ($i = 0; $i < $item->accepted_quantity; $i++) {
            $asset = new Asset();
            $asset->name = $item->item_name;
            $asset->serial = $item->serial_number;
            $asset->model_id = $item->item_type === 'asset' ? 1 : null;
            $asset->category_id = $item->category_id;
            $asset->manufacturer_id = $item->manufacturer_id;
            $asset->supplier_id = $item->supplier_id;
            $asset->purchase_cost = $item->unit_cost;
            $asset->purchase_date = now();
            $asset->order_number = $item->grn->purchase_order_number;
            $asset->status_id = 1;
            $asset->created_by = auth()->id();
            $asset->save();

            $item->update(['accepted_quantity' => $item->accepted_quantity - 1]);
        }

        return $asset ?? null;
    }

    public function approveGrn(GoodsReceivedNote $grn, User $approver): bool
    {
        $grn->approved_by = $approver->id;
        $grn->approved_at = now();
        $grn->status = GoodsReceivedNote::STATUS_LOCKED;
        
        return $grn->save();
    }

    public function getGrnSummary(GoodsReceivedNote $grn): array
    {
        return [
            'grn_number' => $grn->grn_number,
            'status' => $grn->status,
            'supplier' => $grn->supplier?->name,
            'received_date' => $grn->received_date->toDateString(),
            'total_expected' => $grn->items->sum('expected_quantity'),
            'total_received' => $grn->items->sum('received_quantity'),
            'total_accepted' => $grn->items->sum('accepted_quantity'),
            'total_rejected' => $grn->items->sum('rejected_quantity'),
            'total_value' => $grn->items->sum('total_cost'),
            'items_count' => $grn->items->count(),
            'has_discrepancy' => $grn->items->contains(fn($item) => $item->hasDiscrepancy()),
        ];
    }
}
