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
        // 1. Remove old columns if they exist, but first drop foreign keys
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['start_slot_id']);
            $table->dropForeign(['end_slot_id']);
            $table->dropColumn(['start_slot_id', 'end_slot_id']);
        });

        // 2. Create many-to-many pivot table
        Schema::create('booking_time_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            // Optional: you can add timestamps, but for a simple pivot table it might not be strictly necessary
            // $table->timestamps();

            $table->unique(['booking_id', 'time_slot_id'], 'uk_booking_time_slot');
            $table->comment('多節次預約關聯表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_time_slot');

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('start_slot_id')->nullable()->constrained('time_slots')->comment('開始時段ID');
            $table->foreignId('end_slot_id')->nullable()->constrained('time_slots')->comment('結束時段ID');
        });
    }
};
