<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderEmail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['order_snapshot' => 'array', 'sent_at' => 'datetime'];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
