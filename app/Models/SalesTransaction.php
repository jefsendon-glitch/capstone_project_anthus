<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transaction_code', 'transaction_type', 'customer_id', 'customer_name',
        'product_id', 'product_name', 'quantity', 'unit_price', 'total_amount',
        'tendered_amount', 'change_amount', 'payment_method', 'processed_by', 'notes',
        'credit_due_date', 'credit_paid_amount', 'credit_status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'tendered_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'credit_due_date' => 'date',
            'credit_paid_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['total_amount', 'transaction_type', 'payment_method']);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
