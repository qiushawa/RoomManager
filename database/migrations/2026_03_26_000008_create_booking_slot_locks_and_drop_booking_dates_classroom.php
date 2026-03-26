<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slot_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_date_id')->constrained('booking_dates')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            $table->unique(['classroom_id', 'date', 'time_slot_id'], 'uk_booking_slot_locks_classroom_date_slot');
            $table->unique(['booking_date_id', 'time_slot_id'], 'uk_booking_slot_locks_booking_date_slot');
            $table->index(['time_slot_id', 'booking_date_id'], 'idx_booking_slot_locks_slot_booking_date');
        });

        DB::table('booking_slot_locks')
            ->insertUsing(
                ['booking_id', 'booking_date_id', 'time_slot_id', 'classroom_id', 'date', 'created_at', 'updated_at'],
                DB::table('booking_date_time_slot as bdts')
                    ->join('booking_dates as bd', 'bd.id', '=', 'bdts.booking_date_id')
                    ->join('bookings as b', 'b.id', '=', 'bd.booking_id')
                    ->selectRaw('b.id, bd.id, bdts.time_slot_id, b.classroom_id, bd.date, NOW(), NOW()')
                    ->whereIn('b.status_enum', ['pending', 'approved'])
            );

        Schema::table('booking_date_time_slot', function (Blueprint $table) {
            $table->index(['time_slot_id', 'booking_date_id'], 'idx_bdts_time_slot_booking_date');
        });

        Schema::table('booking_dates', function (Blueprint $table) {
            $table->dropForeign('fk_booking_dates_classroom');
            $table->dropIndex('idx_booking_dates_classroom_date');
            $table->dropColumn('classroom_id');
        });
    }

    public function down(): void
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
            $table->dropIndex('idx_bdts_time_slot_booking_date');
        });

        Schema::dropIfExists('booking_slot_locks');
    }
};
