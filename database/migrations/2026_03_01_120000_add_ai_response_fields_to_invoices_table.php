<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('ai_provider')->nullable()->after('total_amount');
            $table->string('ai_model')->nullable()->after('ai_provider');
            $table->longText('ai_raw_response')->nullable()->after('ai_model');
            $table->json('ai_payload')->nullable()->after('ai_raw_response');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_provider',
                'ai_model',
                'ai_raw_response',
                'ai_payload',
            ]);
        });
    }
};

