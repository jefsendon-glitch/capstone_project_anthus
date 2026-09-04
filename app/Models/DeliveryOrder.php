<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code', 'customer_id', 'customer_name', 'customer_address',
        'product_id', 'product_name', 'quantity', 'unit_price', 'total_amount',
        'payment_method', 'status', 'preferred_delivery_date', 'delivered_at',
        'notes', 'placed_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'preferred_delivery_date' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function getItemsSummaryAttribute(): string
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return $this->items->map(fn (DeliveryOrderItem $item) => "{$item->product_name} × {$item->quantity}")->join(', ');
        }

        return "{$this->product_name} × {$this->quantity}";
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
