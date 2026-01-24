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
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete()->comment('教室ID');
            $table->string('course_name', 100)->comment('課程名稱');
            $table->string('teacher_name', 50)->nullable()->comment('教師名稱');
            $table->tinyInteger('day_of_week')->comment('1=Mon ~ 7=Sun');

            $table->foreignId('start_slot_id')->constrained('time_slots')->comment('開始時段ID');
            $table->foreignId('end_slot_id')->constrained('time_slots')->comment('結束時段ID');

            $table->timestamps();

            $table->index(['classroom_id', 'day_of_week'], 'idx_course_classroom_day');

            $table->comment('課程預定資料表');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_schedules');
    }
};
