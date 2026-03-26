<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->index('is_active', 'idx_classrooms_is_active');
            $table->softDeletes();
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->index(['classroom_id', 'day_of_week', 'semester_id'], 'idx_course_classroom_day_semester');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->index(['date', 'is_release_slot'], 'idx_holidays_date_release');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('group', 50)->nullable()->after('key')->comment('設定分組');
            $table->index(['group', 'key'], 'idx_settings_group_key');
        });

        Schema::table('blacklists', function (Blueprint $table) {
            $table->unique('borrower_id', 'uk_blacklists_borrower_id');
        });
    }

    public function down(): void
    {
        Schema::table('blacklists', function (Blueprint $table) {
            $table->dropUnique('uk_blacklists_borrower_id');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_group_key');
            $table->dropColumn('group');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('idx_holidays_date_release');
        });

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_course_classroom_day_semester');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropIndex('idx_classrooms_is_active');
            $table->dropSoftDeletes();
        });
    }
};
