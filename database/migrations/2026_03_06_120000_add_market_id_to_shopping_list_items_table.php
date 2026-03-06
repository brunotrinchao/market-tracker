<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table): void {
            $table->foreignId('market_id')
                ->nullable()
                ->after('product_id')
                ->constrained('markets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('market_id');
        });
    }
};
