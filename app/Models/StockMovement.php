<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movable_type', 'movable_id', 'type', 'quantity_before', 'quantity_after',
        'quantity_delta', 'unit_cost', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'quantity_delta' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    public static function record(
        Model $movable,
        string $type,
        float $delta,
        ?float $before,
        ?float $after,
        ?int $recordedBy,
        ?string $notes = null,
        ?float $unitCost = null,
    ): self {
        return $movable->stockMovements()->create([
            'type' => $type,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_delta' => $delta,
            'unit_cost' => $unitCost,
            'notes' => $notes,
            'recorded_by' => $recordedBy,
        ]);
    }
}
