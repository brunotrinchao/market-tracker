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
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->string('share_token', 64)->nullable()->unique()->after('id');
            $table->date('shopping_date')->nullable()->after('name');
        });

        DB::table('shopping_lists')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('shopping_lists')
                        ->where('id', $row->id)
                        ->update([
                            'share_token' => Str::random(40),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['share_token', 'shopping_date']);
        });
    }
};
