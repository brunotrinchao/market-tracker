<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets')->cascadeOnDelete();
        });

        Schema::table('market_products', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets')->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets')->cascadeOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['market_product_id']);
            $table->foreign('market_product_id')->references('id')->on('market_products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets');
        });

        Schema::table('market_products', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->foreign('market_id')->references('id')->on('markets');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['market_product_id']);
            $table->foreign('market_product_id')->references('id')->on('market_products');
        });
    }
};

