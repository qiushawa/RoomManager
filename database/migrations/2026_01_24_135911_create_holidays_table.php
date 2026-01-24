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
        /*
            這個一個實驗性的資料表
            用於存放假日資訊, 並決定該假日是否釋出時段給預約使用

            * 2026-01-24: 邱聖傑
        */
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('假日日期');
            $table->string('name', 50)->comment('假日名稱');
            $table->boolean('is_release_slot')->default(false)->comment('是否釋出時段給預約');
            $table->timestamps();

            $table->unique('date', 'uk_holidays_date');

            $table->comment('假日資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
