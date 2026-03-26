<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_dates', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->after('booking_id')->comment('教室 ID（由 bookings 回填）');
            $table->index(['classroom_id', 'date'], 'idx_booking_dates_classroom_date');
        });

        DB::table('booking_dates')
            ->join('bookings', 'booking_dates.booking_id', '=', 'bookings.id')
            ->whereNull('booking_dates.classroom_id')
            ->update([
                'booking_dates.classroom_id' => DB::raw('bookings.classroom_id'),
                'booking_dates.updated_at' => now(),
            ]);

        Schema::table('booking_dates', function (Blueprint $table) {
            $table->foreign('classroom_id', 'fk_booking_dates_classroom')
                ->references('id')
                ->on('classrooms')
                ->cascadeOnDelete();
        });

        Schema::table('booking_date_time_slot', function (Blueprint $table) {
            $table->index('time_slot_id', 'idx_booking_date_time_slot_time_slot');
        });
    }

    public function down(): void
    {
        Schema::table('booking_date_time_slot', function (Blueprint $table) {
            $table->dropIndex('idx_booking_date_time_slot_time_slot');
        });

        Schema::table('booking_dates', function (Blueprint $table) {
            $table->dropForeign('fk_booking_dates_classroom');
            $table->dropIndex('idx_booking_dates_classroom_date');
            $table->dropColumn('classroom_id');
        });
    }
};
