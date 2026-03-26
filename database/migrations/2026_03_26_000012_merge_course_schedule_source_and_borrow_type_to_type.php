<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->enum('type', ['course', 'manual', 'borrowed'])
                ->nullable()
                ->after('day_of_week')
                ->comment('course=課表匯入, manual=手動課程, borrowed=一般借用');
        });

        DB::statement("\n            UPDATE course_schedules\n            SET type = CASE\n                WHEN source = 2 OR (source IS NULL AND borrow_type IS NULL) THEN 'course'\n                WHEN borrow_type = 1 THEN 'borrowed'\n                ELSE 'manual'\n            END\n        ");

        DB::statement("ALTER TABLE course_schedules MODIFY `type` ENUM('course','manual','borrowed') NOT NULL DEFAULT 'manual' COMMENT 'course=課表匯入, manual=手動課程, borrowed=一般借用'");

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn(['borrow_type', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->tinyInteger('borrow_type')->nullable()->after('day_of_week')->comment('NULL=匯入, 1=一般借用, 2=課程使用');
            $table->tinyInteger('source')->nullable()->after('borrow_type')->comment('1=手動新增, 2=課表匯入');
        });

        DB::statement("\n            UPDATE course_schedules\n            SET\n                source = CASE\n                    WHEN type = 'course' THEN 2\n                    ELSE 1\n                END,\n                borrow_type = CASE\n                    WHEN type = 'borrowed' THEN 1\n                    WHEN type = 'manual' THEN 2\n                    ELSE NULL\n                END\n        ");

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
