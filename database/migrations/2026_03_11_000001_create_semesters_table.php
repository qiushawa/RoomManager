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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('academic_year')->comment('學年 (例如 114)');
            $table->tinyInteger('semester')->comment('1=上學期, 2=下學期');
            $table->date('start_date')->comment('開始日');
            $table->date('end_date')->comment('結束日');
            $table->timestamps();

            $table->unique(['academic_year', 'semester'], 'uk_semester');
            $table->index(['start_date', 'end_date'], 'idx_semester_dates');

            $table->comment('學期資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
