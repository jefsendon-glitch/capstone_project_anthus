<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const WATER_TYPES = ['purified' => 'Purified Water', 'distilled' => 'Distilled Water', 'mineral' => 'Mineral Water'];

    protected $fillable = ['name', 'category', 'size', 'unit_price', 'stock_quantity', 'low_stock_threshold', 'is_active', 'image_path', 'requires_container_choice'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
            'requires_container_choice' => 'boolean',
        ];
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getStockUnitLabelAttribute(): string
    {
        if ($this->requires_container_choice) return 'containers';

        return match ($this->category) {
            'mineral' => 'bottles',
            'container' => 'pcs',
            default => 'containers',
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? route('products.image', $this, false)
            : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }

    public function gallonStocks(): HasMany
    {
        return $this->hasMany(GallonStock::class);
    }
}
