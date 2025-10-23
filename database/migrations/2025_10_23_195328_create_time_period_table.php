<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_period', function (Blueprint $table) {
            $table->id("period_id")->comment('時段流水號');
            $table->time('start')->comment('開始時間');
            $table->time('end')->comment('結束時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_period');
    }
};
