<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blacklist_id')
                  ->constrained('blacklists')
                  ->onDelete('cascade')
                  ->comment('對應的違規紀錄');
            $table->foreignId('reason_id')
                  ->constrained('blacklist_reasons')
                  ->comment('對應的違規原因');

            $table->unique(['blacklist_id', 'reason_id']);
            $table->comment('黑名單詳細資料表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_details');
    }
};