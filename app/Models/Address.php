<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'market_id',
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
