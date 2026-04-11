<?php

namespace App\Models\Inventory\PurchaseRequisition;

use App\Models\SnipeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequisitionApproval extends SnipeModel
{
    use HasFactory;

    protected $table = 'requisition_approvals';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'pr_id',
        'approver_id',
        'sequence',
        'status',
        'comments',
        'decided_at',
        'created_by',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'pr_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(?string $comments = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->comments = $comments;
        $this->decided_at = now();
        return $this->save();
    }

    public function reject(?string $comments = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->comments = $comments;
        $this->decided_at = now();
        return $this->save();
    }

    public function getNextApproval(): ?self
    {
        return self::where('pr_id', $this->pr_id)
            ->where('sequence', '>', $this->sequence)
            ->where('status', self::STATUS_PENDING)
            ->orderBy('sequence')
            ->first();
    }
}
