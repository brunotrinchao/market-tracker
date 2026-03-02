<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'keywords',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (! $category->slug && $category->name) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

