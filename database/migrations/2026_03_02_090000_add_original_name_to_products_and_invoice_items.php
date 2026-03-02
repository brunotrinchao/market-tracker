<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('name');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('market_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });
    }
};
