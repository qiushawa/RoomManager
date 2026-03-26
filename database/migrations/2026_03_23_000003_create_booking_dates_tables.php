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
        // 使用 chunkById 降低記憶體與鎖定壓力，並以冪等方式避免重複寫入。
        DB::table('bookings')
            ->select(['id', 'date'])
            ->orderBy('id')
            ->chunkById(500, function ($legacyRows) {
                $bookingIds = $legacyRows->pluck('id')->all();

                $timeSlotsByBooking = DB::table('booking_time_slot')
                    ->whereIn('booking_id', $bookingIds)
                    ->select(['booking_id', 'time_slot_id'])
                    ->get()
                    ->groupBy('booking_id');

                DB::transaction(function () use ($legacyRows, $timeSlotsByBooking) {
                    $now = now();

                    foreach ($legacyRows as $row) {
                        if (empty($row->date)) {
                            continue;
                        }

                        $bookingDateId = DB::table('booking_dates')
                            ->where('booking_id', $row->id)
                            ->where('date', $row->date)
                            ->value('id');

                        if (!$bookingDateId) {
                            $bookingDateId = DB::table('booking_dates')->insertGetId([
                                'booking_id' => $row->id,
                                'date' => $row->date,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }

                        $timeSlotIds = collect($timeSlotsByBooking->get($row->id, collect()))
                            ->pluck('time_slot_id')
                            ->map(fn ($id) => (int) $id)
                            ->filter(fn ($id) => $id > 0)
                            ->unique()
                            ->values();

                        if ($timeSlotIds->isEmpty()) {
                            continue;
                        }

                        $existingTimeSlotIds = DB::table('booking_date_time_slot')
                            ->where('booking_date_id', $bookingDateId)
                            ->whereIn('time_slot_id', $timeSlotIds)
                            ->pluck('time_slot_id');

                        $rowsToInsert = $timeSlotIds
                            ->diff($existingTimeSlotIds)
                            ->map(fn ($timeSlotId) => [
                                'booking_date_id' => $bookingDateId,
                                'time_slot_id' => $timeSlotId,
                            ])
                            ->values()
                            ->all();

                        if (!empty($rowsToInsert)) {
                            DB::table('booking_date_time_slot')->insert($rowsToInsert);
                        }
                    }
                });
            });
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
