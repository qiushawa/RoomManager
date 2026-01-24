<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')
                ->constrained('borrowers')
                ->onDelete('cascade')
                ->comment('借用人ID');

            // 實驗性欄位
            $table->timestamp('banned_until')->nullable()->comment('停權截止時間');
            $table->timestamps();

            $table->index('borrower_id', 'idx_blacklists_borrower_id');
            $table->comment('黑名單資料表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklists');
    }
};
