<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            // 1=一般借用, 2=課程使用, NULL=課表匯入
            $table->tinyInteger('borrow_type')->nullable()->after('end_slot_id')->comment('NULL=匯入, 1=一般借用, 2=課程使用');
            $table->date('start_date')->nullable()->after('borrow_type')->comment('手動記錄有效開始日');
            $table->date('end_date')->nullable()->after('start_date')->comment('手動記錄有效結束日');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn(['borrow_type', 'start_date', 'end_date']);
        });
    }
};
