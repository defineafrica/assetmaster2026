<?php

namespace App\Models\Inventory\GoodsReceivedNote;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\SnipeModel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnItem extends SnipeModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'grn_items';

    public const CONDITION_GOOD = 'good';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_DEFECTIVE = 'defective';
    public const CONDITION_REJECTED = 'rejected';

    protected $fillable = [
        'grn_id',
        'item_type',
        'item_name',
        'item_description',
        'category_id',
        'manufacturer_id',
        'supplier_id',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'condition',
        'unit_cost',
        'total_cost',
        'serial_number',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'received_quantity' => 'integer',
        'accepted_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasDiscrepancy(): bool
    {
        return $this->received_quantity != $this->expected_quantity;
    }

    public function isFullyAccepted(): bool
    {
        return $this->accepted_quantity == $this->received_quantity;
    }

    public function isPartiallyAccepted(): bool
    {
        return $this->accepted_quantity > 0 && $this->accepted_quantity < $this->received_quantity;
    }

    public function isFullyRejected(): bool
    {
        return $this->rejected_quantity == $this->received_quantity;
    }

    public function calculateTotals(): void
    {
        $this->total_cost = $this->accepted_quantity * $this->unit_cost;
    }
}
