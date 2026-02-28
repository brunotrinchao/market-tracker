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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('market_product_id')->constrained();
            $table->decimal('quantity', 10, 3); // Qtde total de ítens: 0.982 [cite: 8]
            $table->decimal('unit_price', 10, 2); // Preço unitário extraído
            $table->decimal('total_price', 10, 2); // Valor total R$: 18,64 [cite: 8]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
