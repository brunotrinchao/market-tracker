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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained();
            $table->string('access_key')->unique()->nullable(); // Chave de acesso da NFC-e [cite: 15]
            $table->dateTime('issued_at'); // Data Emissão: 12/02/2026 [cite: 32]
            $table->decimal('total_amount', 10, 2); // R$ 193,40 [cite: 13, 38]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
