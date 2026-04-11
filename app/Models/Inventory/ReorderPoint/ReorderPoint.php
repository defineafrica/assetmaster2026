<?php

namespace App\Models\Inventory\ReorderPoint;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\SnipeModel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReorderPoint extends SnipeModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'reorder_points';

    protected $fillable = [
        'item_type',
        'item_id',
        'category_id',
        'reorder_point',
        'safety_stock',
        'reorder_quantity',
        'preferred_supplier_id',
        'auto_replenish',
        'lead_time_days',
        'alert_threshold_days',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reorder_point' => 'integer',
        'safety_stock' => 'integer',
        'reorder_quantity' => 'integer',
        'lead_time_days' => 'integer',
        'alert_threshold_days' => 'integer',
        'auto_replenish' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function preferredSupplier()
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getReorderPointForItem(string $itemType, int $itemId): ?self
    {
        return self::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->first();
    }

    public static function getItemsBelowReorderPoint(): \Illuminate\Database\Eloquent\Collection
    {
        $consumables = \App\Models\Consumable::where('qty', '>', 0)
            ->whereHas('reorderPoint', function ($query) {
                $query->whereColumn('qty', '<=', 'reorder_point');
            })
            ->with('reorderPoint')
            ->get();

        $accessories = \App\Models\Accessory::where('qty', '>', 0)
            ->whereHas('reorderPoint', function ($query) {
                $query->whereColumn('qty', '<=', 'reorder_point');
            })
            ->with('reorderPoint')
            ->get();

        return $consumables->concat($accessories);
    }

    public static function calculateSafetyStock(int $leadTimeDays, float $averageDailyUsage, float $zScore = 1.65): float
    {
        return ceil($zScore * $averageDailyUsage * sqrt($leadTimeDays));
    }
}
