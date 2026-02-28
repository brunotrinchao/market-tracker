<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('market_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('external_code'); // Ex: Código 2634029 [cite: 8]
            $table->string('unit'); // UN, KG, FR, PT [cite: 8, 12]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_products');
    }
};
