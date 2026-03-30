<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')
                ->default(Booking::LEVEL_PENDING)
                ->after('status_enum')
                ->comment('借用優先等級，數值越高優先；課表固定最高');
            $table->index(['classroom_id', 'status_enum', 'level'], 'idx_bookings_classroom_status_level');
        });

        DB::table('bookings')
            ->where('status_enum', Booking::STATUS_APPROVED)
            ->update(['level' => Booking::LEVEL_APPROVED]);

        DB::table('bookings')
            ->where('status_enum', Booking::STATUS_PENDING)
            ->update(['level' => Booking::LEVEL_PENDING]);

        DB::table('bookings')
            ->whereIn('status_enum', [Booking::STATUS_REJECTED, Booking::STATUS_CANCELLED])
            ->update(['level' => Booking::LEVEL_REJECTED]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_classroom_status_level');
            $table->dropColumn('level');
        });
    }
};
