<?php

namespace App\Services\Inventory;

use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisitionItem;
use App\Models\Inventory\PurchaseRequisition\RequisitionApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequisitionWorkflowService
{
    public function createRequisition(array $data): PurchaseRequisition
    {
        $pr = new PurchaseRequisition();
        $pr->pr_number = PurchaseRequisition::generatePrNumber();
        $pr->requesting_user_id = $data['requesting_user_id'] ?? auth()->id();
        $pr->department_id = $data['department_id'] ?? null;
        $pr->status = PurchaseRequisition::STATUS_DRAFT;
        $pr->notes = $data['notes'] ?? null;
        $pr->justification = $data['justification'] ?? null;
        $pr->estimated_total = 0;
        $pr->created_by = auth()->id();
        $pr->save();

        return $pr;
    }

    public function addItem(PurchaseRequisition $pr, array $itemData): PurchaseRequisitionItem
    {
        $item = new PurchaseRequisitionItem();
        $item->pr_id = $pr->id;
        $item->item_type = $itemData['item_type'] ?? 'asset';
        $item->item_id = $itemData['item_id'] ?? null;
        $item->item_name = $itemData['item_name'];
        $item->description = $itemData['description'] ?? null;
        $item->category_id = $itemData['category_id'] ?? null;
        $item->manufacturer_id = $itemData['manufacturer_id'] ?? null;
        $item->supplier_id = $itemData['supplier_id'] ?? null;
        $item->quantity = $itemData['quantity'] ?? 1;
        $item->unit_cost = $itemData['unit_cost'] ?? 0;
        $item->justification = $itemData['justification'] ?? null;
        $item->status = PurchaseRequisitionItem::STATUS_PENDING;
        $item->created_by = auth()->id();
        $item->calculateTotal();
        $item->save();

        $pr->recalculateTotal();

        return $item;
    }

    public function updateItem(PurchaseRequisitionItem $item, array $data): PurchaseRequisitionItem
    {
        $item->fill($data);
        
        if (isset($data['quantity']) || isset($data['unit_cost'])) {
            $item->quantity = $data['quantity'] ?? $item->quantity;
            $item->unit_cost = $data['unit_cost'] ?? $item->unit_cost;
            $item->calculateTotal();
        }
        
        $item->updated_by = auth()->id();
        $item->save();

        $item->purchaseRequisition->recalculateTotal();

        return $item;
    }

    public function removeItem(PurchaseRequisitionItem $item): bool
    {
        $pr = $item->purchaseRequisition;
        
        if (!$pr->isDraft()) {
            return false;
        }

        $item->delete();
        $pr->recalculateTotal();

        return true;
    }

    public function submit(PurchaseRequisition $pr): bool
    {
        if (!$pr->isSubmittable()) {
            return false;
        }

        $pr->status = PurchaseRequisition::STATUS_SUBMITTED;
        
        return $pr->save();
    }

    public function approve(PurchaseRequisition $pr, User $approver, ?string $comments = null): bool
    {
        if ($pr->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return false;
        }

        DB::beginTransaction();
        try {
            $pr->status = PurchaseRequisition::STATUS_APPROVED;
            $pr->approved_by = $approver->id;
            $pr->approved_at = now();
            
            if ($comments) {
                $pr->notes = ($pr->notes ? $pr->notes . "\n" : '') . "Approval: {$comments}";
            }
            
            $pr->save();

            foreach ($pr->items as $item) {
                $item->status = PurchaseRequisitionItem::STATUS_APPROVED;
                $item->save();
            }

            DB::commit();
            Log::info('Purchase requisition approved', ['pr_id' => $pr->id, 'approver_id' => $approver->id]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve purchase requisition', ['pr_id' => $pr->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function reject(PurchaseRequisition $pr, User $rejector, string $reason): bool
    {
        if (!in_array($pr->status, [PurchaseRequisition::STATUS_SUBMITTED, PurchaseRequisition::STATUS_APPROVED])) {
            return false;
        }

        DB::beginTransaction();
        try {
            $pr->status = PurchaseRequisition::STATUS_REJECTED;
            $pr->rejected_by = $rejector->id;
            $pr->rejected_at = now();
            $pr->rejection_reason = $reason;
            $pr->save();

            foreach ($pr->items as $item) {
                $item->status = PurchaseRequisitionItem::STATUS_REJECTED;
                $item->rejection_reason = $reason;
                $item->save();
            }

            DB::commit();
            Log::info('Purchase requisition rejected', ['pr_id' => $pr->id, 'rejector_id' => $rejector->id, 'reason' => $reason]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject purchase requisition', ['pr_id' => $pr->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function fulfill(PurchaseRequisition $pr): bool
    {
        if ($pr->status !== PurchaseRequisition::STATUS_APPROVED) {
            return false;
        }

        DB::beginTransaction();
        try {
            $pr->status = PurchaseRequisition::STATUS_FULFILLED;
            $pr->save();

            foreach ($pr->items as $item) {
                $item->status = PurchaseRequisitionItem::STATUS_FULFILLED;
                $item->save();
            }

            DB::commit();
            Log::info('Purchase requisition fulfilled', ['pr_id' => $pr->id]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to fulfill purchase requisition', ['pr_id' => $pr->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getRequisitionSummary(PurchaseRequisition $pr): array
    {
        return [
            'pr_number' => $pr->pr_number,
            'status' => $pr->status,
            'requesting_user' => $pr->requestingUser?->name,
            'department' => $pr->department?->name,
            'estimated_total' => number_format($pr->estimated_total, 2),
            'items_count' => $pr->items->count(),
            'total_quantity' => $pr->items->sum('quantity'),
            'approved_items' => $pr->items->where('status', PurchaseRequisitionItem::STATUS_APPROVED)->count(),
            'rejected_items' => $pr->items->where('status', PurchaseRequisitionItem::STATUS_REJECTED)->count(),
            'pending_items' => $pr->items->where('status', PurchaseRequisitionItem::STATUS_PENDING)->count(),
            'created_at' => $pr->created_at->toIso8601String(),
            'submitted_at' => $pr->created_at->toIso8601String(),
            'approved_at' => $pr->approved_at?->toIso8601String(),
        ];
    }
}
