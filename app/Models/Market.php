<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'name',
        'cnpj',
        'logo',
        'access_key',
        'issued_at',
        'total_amount',
        'ai_provider',
        'ai_model',
        'ai_payload',
        'ai_raw_response',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function marketProducts(): HasMany
    {
        return $this->hasMany(MarketProduct::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'market_products')
            ->withPivot(['external_code', 'unit'])
            ->withTimestamps();
    }
}
