<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyUsage extends Model
{
    protected $fillable = ['operation', 'operation_date', 'notes', 'recorded_by', 'items'];

    protected function casts(): array
    {
        return ['operation_date' => 'date', 'items' => 'array'];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
