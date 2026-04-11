<?php

namespace App\Models\Inventory\Reservation;

use App\Models\Department;
use App\Models\SnipeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends SnipeModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservations';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'reservation_number',
        'item_type',
        'item_id',
        'quantity',
        'reserved_by',
        'department_id',
        'project_code',
        'notes',
        'status',
        'expires_at',
        'fulfilled_at',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    public function reserver()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fulfill(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->status = self::STATUS_FULFILLED;
        $this->fulfilled_at = now();
        return $this->save();
    }

    public function cancel(): bool
    {
        if (in_array($this->status, [self::STATUS_FULFILLED, self::STATUS_CANCELLED])) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        return $this->save();
    }

    public function markExpired(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === self::STATUS_ACTIVE;
    }

    public static function generateReservationNumber(): string
    {
        $prefix = 'RES';
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public static function getActiveReservationsForItem(string $itemType, int $itemId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    public static function getTotalReservedQuantity(string $itemType, int $itemId): int
    {
        return self::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('quantity');
    }
}
