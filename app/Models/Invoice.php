<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'market_id',
        'access_key',
        'issued_at',
        'total_amount',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
