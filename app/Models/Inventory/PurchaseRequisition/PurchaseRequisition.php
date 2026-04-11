<?php

namespace App\Models\Inventory\PurchaseRequisition;

use App\Models\Department;
use App\Models\SnipeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends SnipeModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_requisitions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FULFILLED = 'fulfilled';

    protected $fillable = [
        'pr_number',
        'requesting_user_id',
        'department_id',
        'status',
        'notes',
        'justification',
        'estimated_total',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function requestingUser()
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
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
        return $this->hasMany(PurchaseRequisitionItem::class, 'pr_id')->orderBy('created_at');
    }

    public function approvals()
    {
        return $this->hasMany(RequisitionApproval::class, 'pr_id')->orderBy('sequence');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmittable(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items->count() > 0;
    }

    public function submit(): bool
    {
        if (!$this->isSubmittable()) {
            return false;
        }

        $this->status = self::STATUS_SUBMITTED;
        return $this->save();
    }

    public function approve(User $approver, ?string $comments = null): bool
    {
        if (!in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_APPROVED])) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        
        if ($comments) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Approval comment: {$comments}";
        }

        return $this->save();
    }

    public function reject(User $rejector, string $reason): bool
    {
        if (!in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_APPROVED])) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->rejected_by = $rejector->id;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function markFulfilled(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_FULFILLED;
        return $this->save();
    }

    public function calculateTotal(): float
    {
        return $this->items->sum('total_cost');
    }

    public function recalculateTotal(): void
    {
        $this->estimated_total = $this->calculateTotal();
        $this->save();
    }

    public static function generatePrNumber(): string
    {
        $prefix = config('snipe.pr.prefix', 'PR');
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
