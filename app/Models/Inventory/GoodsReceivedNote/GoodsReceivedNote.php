<?php

namespace App\Models\Inventory\GoodsReceivedNote;

use App\Models\SnipeModel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceivedNote extends SnipeModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'goods_received_notes';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'grn_number',
        'supplier_id',
        'received_by',
        'received_date',
        'purchase_order_number',
        'status',
        'notes',
        'inspection_status',
        'inspection_notes',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class, 'grn_id')->orderBy('created_at');
    }

    public function inspections()
    {
        return $this->hasMany(GrnInspection::class, 'grn_id')->orderBy('created_at');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPostable(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items->count() > 0;
    }

    public function isLockable(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function post(): bool
    {
        if (!$this->isPostable()) {
            return false;
        }

        $this->status = self::STATUS_POSTED;
        return $this->save();
    }

    public function lock(): bool
    {
        if (!$this->isLockable()) {
            return false;
        }

        $this->status = self::STATUS_LOCKED;
        return $this->save();
    }

    public static function generateGrnNumber(): string
    {
        $prefix = 'GRN';
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public static function getNextGrnNumber(): string
    {
        $prefix = config('snipe/grn.prefix', 'GRN');
        $date = now()->format('Ymd');
        
        $latestGrn = self::where('grn_number', 'like', "{$prefix}-{$date}%")
            ->orderBy('grn_number', 'desc')
            ->first();
        
        if ($latestGrn) {
            $parts = explode('-', $latestGrn->grn_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
