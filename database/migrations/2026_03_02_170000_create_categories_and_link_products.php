<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('keywords')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('name')
                ->constrained('categories')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('products', 'category')) {
            $legacyNames = DB::table('products')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->map(fn ($name): string => trim((string) $name))
                ->filter()
                ->values();

            $map = [];
            foreach ($legacyNames as $name) {
                $slug = Str::slug($name);
                if ($slug === '') {
                    $slug = 'categoria-' . Str::random(8);
                }

                $id = DB::table('categories')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'keywords' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $map[$name] = $id;
            }

            foreach ($map as $legacyName => $categoryId) {
                DB::table('products')
                    ->where('category', $legacyName)
                    ->update(['category_id' => $categoryId]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('category')->nullable()->after('name');
            });
        }

        if (Schema::hasColumn('products', 'category_id')) {
            $rows = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select('products.id as product_id', 'categories.name as category_name')
                ->get();

            foreach ($rows as $row) {
                DB::table('products')
                    ->where('id', $row->product_id)
                    ->update(['category' => $row->category_name]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('categories');
    }
};

