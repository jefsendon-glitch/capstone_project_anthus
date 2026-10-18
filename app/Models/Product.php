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

    public const WATER_TYPES = ['purified' => 'Purified Water', 'distilled' => 'Distilled Water', 'alkaline' => 'Alkaline Water'];

    protected $fillable = ['name', 'category', 'size', 'unit_price', 'stock_quantity', 'round_stock', 'slim_stock', 'low_stock_threshold', 'is_active', 'image_path', 'requires_container_choice'];

    public function availableStock(?string $container): int
    {
        if (! $this->requires_container_choice) return (int) $this->stock_quantity;
        return match ($container) {
            'round' => (int) $this->round_stock,
            'slim' => (int) $this->slim_stock,
            default => max(0, $this->stock_quantity - $this->round_stock - $this->slim_stock),
        };
    }

    /** Call while holding the product row lock, inside a transaction. */
    public function deductStock(int $quantity, ?string $container): void
    {
        if ($quantity > $this->availableStock($container)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Not enough '.($container ? ucfirst($container).' Gallon' : 'unassigned').' stock for '.$this->name.'.']);
        }
        if ($this->requires_container_choice && in_array($container, ['round', 'slim'], true)) {
            $this->decrement($container.'_stock', $quantity);
        }
        $this->decrement('stock_quantity', $quantity);
    }

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
            'other' => 'pcs',
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
