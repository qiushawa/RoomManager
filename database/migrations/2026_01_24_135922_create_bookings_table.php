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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('borrowers')->comment('借用人ID');
            $table->foreignId('classroom_id')->constrained('classrooms')->comment('教室ID');
            $table->foreignId('start_slot_id')->constrained('time_slots')->comment('開始時段ID');
            $table->foreignId('end_slot_id')->constrained('time_slots')->comment('結束時段ID');
            $table->string('reason', 100)->comment('借用事由');
            $table->string('teacher', 50)->comment('教師名稱');
            $table->tinyInteger('status')->default(0)->comment('0=待審核, 1=核准, 2=拒絕, 3=取消');
            $table->date('date')->comment('預約日期');
            $table->timestamps();

            $table->index(['classroom_id', 'date'], 'idx_bookings_classroom_date');
            $table->comment('教室預約資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
