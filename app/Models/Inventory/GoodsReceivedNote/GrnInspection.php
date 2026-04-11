<?php

namespace App\Models\Inventory\GoodsReceivedNote;

use App\Models\SnipeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrnInspection extends SnipeModel
{
    use HasFactory;

    protected $table = 'grn_inspections';

    public const RESULT_ACCEPTED = 'accepted';
    public const RESULT_REJECTED = 'rejected';
    public const RESULT_PARTIAL = 'partial';
    public const RESULT_PENDING = 'pending';

    protected $fillable = [
        'grn_id',
        'grn_item_id',
        'inspected_by',
        'inspection_date',
        'result',
        'notes',
        'discrepancy_type',
        'discrepancy_notes',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function grnItem()
    {
        return $this->belongsTo(GrnItem::class, 'grn_item_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAccepted(): bool
    {
        return $this->result === self::RESULT_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->result === self::RESULT_REJECTED;
    }

    public function isPending(): bool
    {
        return $this->result === self::RESULT_PENDING;
    }
}
