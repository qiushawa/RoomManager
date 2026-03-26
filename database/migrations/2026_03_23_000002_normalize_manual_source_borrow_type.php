<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 長期借用手動記錄一律視為課程使用
        DB::table('course_schedules')
            ->where('source', 1)
            ->where(function ($query) {
                $query->whereNull('borrow_type')
                    ->orWhere('borrow_type', '!=', 2);
            })
            ->update(['borrow_type' => 2]);

        // 相容舊資料：source 仍為 NULL 時依 borrow_type 回填
        DB::table('course_schedules')
            ->whereNull('source')
            ->whereNull('borrow_type')
            ->update(['source' => 2]);

        DB::table('course_schedules')
            ->whereNull('source')
            ->whereNotNull('borrow_type')
            ->update(['source' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 此修正屬資料正規化，不回復
    }
};
