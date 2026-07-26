<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterProductionLog extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'gallons_produced', 'produced_by', 'production_date', 'notes'];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'gallons_produced' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function producedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }
}
