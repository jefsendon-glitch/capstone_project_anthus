<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = ['equipment_name', 'category', 'last_maintenance_date', 'next_due_date', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'last_maintenance_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'ok' && $this->next_due_date->isPast();
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'overdue')
                ->orWhere(function (Builder $query) {
                    $query->where('status', '!=', 'ok')->where('next_due_date', '<', now());
                });
        });
    }
}
