<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('booking_time_slot');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('date')->nullable()->comment('預約日期（舊欄位，回滾使用）');
            $table->index(['classroom_id', 'date'], 'idx_bookings_classroom_date');
        });

        Schema::create('booking_time_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->unique(['booking_id', 'time_slot_id'], 'uk_booking_time_slot');
            $table->comment('多節次預約關聯表');
        });
    }
};
