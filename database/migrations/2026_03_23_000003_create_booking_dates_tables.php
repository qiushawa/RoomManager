<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->date('date')->comment('借用日期');
            $table->timestamps();

            $table->unique(['booking_id', 'date'], 'uk_booking_dates_booking_date');
            $table->index(['date'], 'idx_booking_dates_date');
        });

        Schema::create('booking_date_time_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_date_id')->constrained('booking_dates')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();

            $table->unique(['booking_date_id', 'time_slot_id'], 'uk_booking_date_time_slot');
        });

        // 將既有單日資料回填至新結構，確保新舊邏輯可共存。
        $legacyRows = DB::table('bookings')->select(['id', 'date'])->orderBy('id')->get();
        foreach ($legacyRows as $row) {
            $bookingDateId = DB::table('booking_dates')->insertGetId([
                'booking_id' => $row->id,
                'date' => $row->date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $timeSlotIds = DB::table('booking_time_slot')
                ->where('booking_id', $row->id)
                ->pluck('time_slot_id');

            foreach ($timeSlotIds as $timeSlotId) {
                DB::table('booking_date_time_slot')->insert([
                    'booking_date_id' => $bookingDateId,
                    'time_slot_id' => $timeSlotId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_date_time_slot');
        Schema::dropIfExists('booking_dates');
    }
};
