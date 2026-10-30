<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected function casts(): array
    {
        return ['delivery_fee_per_gallon' => 'decimal:2'];
    }

    protected $fillable = [
        'business_name', 'logo_path', 'address', 'contact_number',
        'contact_email', 'currency', 'delivery_fee_per_gallon',
    ];

    protected $attributes = [
        'business_name' => 'Shaunti Water Refilling',
        'currency' => 'PHP', 'delivery_fee_per_gallon' => 0,
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([]);
    }
}
