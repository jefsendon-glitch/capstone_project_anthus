<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['po_number', 'supplier_id', 'status', 'ordered_at', 'expected_date', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'expected_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->items->every(fn (PurchaseOrderItem $item) => $item->quantity_received >= $item->quantity_ordered);
    }

    public function recalculateStatus(): void
    {
        if ($this->status === 'cancelled' || $this->status === 'draft') {
            return;
        }

        $items = $this->items;
        $totalReceived = $items->sum('quantity_received');

        $status = match (true) {
            $totalReceived <= 0 => 'ordered',
            $items->every(fn (PurchaseOrderItem $item) => $item->quantity_received >= $item->quantity_ordered) => 'received',
            default => 'partially_received',
        };

        $this->update(['status' => $status]);
    }
}
