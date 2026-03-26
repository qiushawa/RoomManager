<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('teacher')->comment('預約狀態 0=待審核,1=通過,2=拒絕,3=取消');
        });

        DB::table('bookings')->update([
            'status' => DB::raw("CASE status_enum WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 WHEN 'cancelled' THEN 3 ELSE 0 END"),
        ]);
    }
};
