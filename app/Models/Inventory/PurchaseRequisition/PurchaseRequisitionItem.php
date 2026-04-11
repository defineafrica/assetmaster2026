<?php

namespace App\Models\Inventory\PurchaseRequisition;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\SnipeModel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequisitionItem extends SnipeModel
{
    use HasFactory;

    protected $table = 'purchase_requisition_items';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FULFILLED = 'fulfilled';

    protected $fillable = [
        'pr_id',
        'item_type',
        'item_id',
        'item_name',
        'description',
        'category_id',
        'manufacturer_id',
        'supplier_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'justification',
        'status',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'pr_id');
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

    public function calculateTotal(): void
    {
        $this->total_cost = $this->quantity * $this->unit_cost;
    }

    public function approve(): bool
    {
        $this->status = self::STATUS_APPROVED;
        return $this->save();
    }

    public function reject(string $reason): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        return $this->save();
    }

    public function markFulfilled(): bool
    {
        $this->status = self::STATUS_FULFILLED;
        return $this->save();
    }
}
