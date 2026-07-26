<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'business_name', 'logo_path', 'address', 'contact_number',
        'contact_email', 'currency',
    ];

    protected $attributes = [
        'business_name' => 'Shaunti Water Refilling',
        'currency' => 'PHP',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([]);
    }
}
