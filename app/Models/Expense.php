<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = ['Supplies & Materials', 'Utilities', 'Rent', 'Payroll', 'Cleaning & Sanitation', 'Equipment Maintenance', 'Delivery & Fuel', 'Administrative', 'Other'];

    protected $fillable = ['expense_date', 'category', 'description', 'amount', 'reference', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
