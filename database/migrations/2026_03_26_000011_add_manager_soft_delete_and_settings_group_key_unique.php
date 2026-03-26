<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->softDeletes();
        });

        DB::table('settings')
            ->whereNull('group')
            ->update(['group' => 'system']);

        DB::statement("ALTER TABLE settings MODIFY `group` VARCHAR(50) NOT NULL DEFAULT 'system' COMMENT '設定分組'");

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_group_key');
            $table->dropUnique('settings_key_unique');
            $table->unique(['group', 'key'], 'uk_settings_group_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('uk_settings_group_key');
            $table->unique('key');
            $table->index(['group', 'key'], 'idx_settings_group_key');
        });

        DB::statement("ALTER TABLE settings MODIFY `group` VARCHAR(50) NULL COMMENT '設定分組'");

        Schema::table('managers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
