<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_order_id', 'itemable_type', 'itemable_id', 'quantity_ordered', 'quantity_received', 'unit_cost'];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:2',
            'quantity_received' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }
}
