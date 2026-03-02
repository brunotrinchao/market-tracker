<?php

namespace App\Models;

use App\Services\Products\ProductCategoryClassifier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'original_name',
        'category_id',
        'image',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->category_id || ! $product->name) {
                return;
            }

            $categoryId = app(ProductCategoryClassifier::class)->inferCategoryId($product->name);

            if ($categoryId) {
                $product->category_id = $categoryId;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function marketProducts(): HasMany
    {
        return $this->hasMany(MarketProduct::class);
    }

    public function markets(): BelongsToMany
    {
        return $this->belongsToMany(Market::class, 'market_products')
            ->withPivot(['external_code', 'unit'])
            ->withTimestamps();
    }

    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    public function shoppingLists(): BelongsToMany
    {
        return $this->belongsToMany(ShoppingList::class, 'shopping_list_items')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}
