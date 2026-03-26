<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_bookings_after_update_lock');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_booking_dates_after_update_lock');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_bdts_after_delete_lock');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_bdts_after_update_lock');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_bdts_after_insert_lock');
    }

    public function down(): void
    {
        // Triggers are intentionally not restored. Lock sync is handled by Laravel service logic.
    }
};
