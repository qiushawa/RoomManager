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
        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->comment('管理員帳號');
            $table->string('password', 255)->comment('管理員密碼');
            $table->string('name', 50)->comment('顯示名稱');
            $table->string('email', 100)->nullable()->comment('電子郵件');
            $table->timestamps();

            $table->unique('username', 'uk_managers_username');
            $table->index('email', 'idx_managers_email');

            $table->comment('管理員資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('managers');
    }
};
