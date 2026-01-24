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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 7)->comment('教室代號');
            $table->string('name', 25)->comment('教室名稱');
            $table->boolean('is_active')->default(true)->comment('1=啟用, 0=停用');
            $table->timestamps();

            $table->unique('code', 'uk_classrooms_code');
            $table->comment('教室資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
