<?php

namespace App\Services\Inventory;

use App\Models\Accessory;
use App\Models\Consumable;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use Illuminate\Support\Collection;

class ReorderPointEngine
{
    public function checkReorderPoints(): Collection
    {
        $itemsBelowReorder = collect();

        $consumables = $this->checkConsumableReorderPoints();
        $accessories = $this->checkAccessoryReorderPoints();

        return $itemsBelowReorder->concat($consumables)->concat($accessories);
    }

    protected function checkConsumableReorderPoints(): Collection
    {
        $items = collect();
        $consumables = Consumable::where('qty', '>', 0)->with('category')->get();

        foreach ($consumables as $consumable) {
            $reorderPoint = ReorderPoint::getReorderPointForItem('consumables', $consumable->id);
            
            if (!$reorderPoint) {
                continue;
            }

            if ($consumable->qty <= $reorderPoint->reorder_point) {
                $items->push([
                    'item_type' => 'consumables',
                    'item_id' => $consumable->id,
                    'item_name' => $consumable->name,
                    'current_quantity' => $consumable->qty,
                    'reorder_point' => $reorderPoint->reorder_point,
                    'safety_stock' => $reorderPoint->safety_stock,
                    'reorder_quantity' => $reorderPoint->reorder_quantity,
                    'preferred_supplier' => $reorderPoint->preferredSupplier?->name,
                    'urgency' => $this->calculateUrgency($consumable->qty, $reorderPoint),
                ]);
            }
        }

        return $items;
    }

    protected function checkAccessoryReorderPoints(): Collection
    {
        $items = collect();
        $accessories = Accessory::where('qty', '>', 0)->with('category')->get();

        foreach ($accessories as $accessory) {
            $reorderPoint = ReorderPoint::getReorderPointForItem('accessories', $accessory->id);
            
            if (!$reorderPoint) {
                continue;
            }

            if ($accessory->qty <= $reorderPoint->reorder_point) {
                $items->push([
                    'item_type' => 'accessories',
                    'item_id' => $accessory->id,
                    'item_name' => $accessory->name,
                    'current_quantity' => $accessory->qty,
                    'reorder_point' => $reorderPoint->reorder_point,
                    'safety_stock' => $reorderPoint->safety_stock,
                    'reorder_quantity' => $reorderPoint->reorder_quantity,
                    'preferred_supplier' => $reorderPoint->preferredSupplier?->name,
                    'urgency' => $this->calculateUrgency($accessory->qty, $reorderPoint),
                ]);
            }
        }

        return $items;
    }

    protected function calculateUrgency(int $currentQty, ReorderPoint $reorderPoint): string
    {
        if ($currentQty <= $reorderPoint->safety_stock) {
            return 'critical';
        }

        if ($currentQty <= $reorderPoint->reorder_point * 0.5) {
            return 'high';
        }

        return 'medium';
    }

    public function calculateReorderQuantity(ReorderPoint $reorderPoint, float $averageUsage): int
    {
        $daysUntilReview = $reorderPoint->lead_time_days ?? 7;
        $projectedUsage = $averageUsage * $daysUntilReview;
        
        return max($reorderPoint->reorder_quantity, (int) ceil($projectedUsage));
    }

    public function suggestReorderQuantity(string $itemType, int $itemId): int
    {
        $reorderPoint = ReorderPoint::getReorderPointForItem($itemType, $itemId);
        
        if (!$reorderPoint) {
            return 0;
        }

        $averageUsage = $this->calculateAverageUsage($itemType, $itemId);
        
        return $this->calculateReorderQuantity($reorderPoint, $averageUsage);
    }

    protected function calculateAverageUsage(string $itemType, int $itemId): float
    {
        $daysToAnalyze = 30;
        $startDate = now()->subDays($daysToAnalyze);

        $checkouts = \App\Models\Actionlog::where('action_type', 'checkout')
            ->where('item_type', $itemType === 'consumables' ? Consumable::class : Accessory::class)
            ->where('item_id', $itemId)
            ->where('created_at', '>=', $startDate)
            ->count();

        return $checkouts / $daysToAnalyze;
    }

    public function getReorderReport(): array
    {
        $itemsBelowReorder = $this->checkReorderPoints();

        return [
            'total_items_below_reorder' => $itemsBelowReorder->count(),
            'critical_count' => $itemsBelowReorder->where('urgency', 'critical')->count(),
            'high_count' => $itemsBelowReorder->where('urgency', 'high')->count(),
            'medium_count' => $itemsBelowReorder->where('urgency', 'medium')->count(),
            'items' => $itemsBelowReorder->sortByDesc('urgency')->values(),
        ];
    }
}
