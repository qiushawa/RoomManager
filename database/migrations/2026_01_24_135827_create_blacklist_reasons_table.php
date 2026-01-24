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
        Schema::create('blacklist_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason', 50)->comment('事由內容');
            $table->timestamps();

            $table->comment('黑名單事由資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist_reasons');
    }
};
