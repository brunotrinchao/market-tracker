<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingList extends Model
{
    protected $fillable = [
        'share_token',
        'name',
        'shopping_date',
        'notes',
    ];

    protected $casts = [
        'shopping_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shopping_list_items')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}
