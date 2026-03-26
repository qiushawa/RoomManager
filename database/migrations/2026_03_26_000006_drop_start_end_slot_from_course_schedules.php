<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropForeign(['start_slot_id']);
            $table->dropForeign(['end_slot_id']);
            $table->dropColumn(['start_slot_id', 'end_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->foreignId('start_slot_id')->nullable()->after('day_of_week')->constrained('time_slots')->comment('開始時段ID');
            $table->foreignId('end_slot_id')->nullable()->after('start_slot_id')->constrained('time_slots')->comment('結束時段ID');
        });
    }
};
