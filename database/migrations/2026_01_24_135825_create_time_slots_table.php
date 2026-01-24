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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 10)->comment('時段名稱');
            $table->time('start_time')->comment('開始時間');
            $table->time('end_time')->comment('結束時間');
            $table->timestamps();

            $table->unique('name', 'uk_time_slots_name');
            $table->comment('時段資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
