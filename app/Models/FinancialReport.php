<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    protected $fillable = ['period_type', 'date_from', 'date_to', 'summary', 'generated_by'];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'summary' => 'array'];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
