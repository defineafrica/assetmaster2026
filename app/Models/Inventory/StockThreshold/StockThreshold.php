<?php

namespace App\Models\Inventory\StockThreshold;

use App\Models\SnipeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockThreshold extends SnipeModel
{
    use HasFactory;

    protected $table = 'stock_thresholds';

    protected $fillable = [
        'item_type',
        'item_id',
        'min_quantity',
        'reorder_quantity',
        'alert_email',
        'send_sms',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'reorder_quantity' => 'integer',
        'send_sms' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getThresholdForItem(string $itemType, int $itemId): ?self
    {
        return self::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->first();
    }

    public static function getAllThresholds(): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['creator', 'updater'])
            ->where('is_active', true)
            ->orderBy('item_type')
            ->orderBy('item_id')
            ->get();
    }
}
