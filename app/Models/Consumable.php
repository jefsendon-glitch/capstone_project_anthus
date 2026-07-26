<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Consumable extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'unit', 'quantity', 'low_stock_threshold', 'unit_cost', 'supplier_id', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }
}
