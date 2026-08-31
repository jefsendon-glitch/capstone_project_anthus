<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['delivery_order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_amount'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total_amount' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
