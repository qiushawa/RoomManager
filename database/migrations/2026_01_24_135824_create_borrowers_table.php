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
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('identity_code', 8)->comment('學號或教職員編號');
            $table->string('name', 20)->comment('借用人姓名');
            $table->string('email', 100)->nullable()->comment('電子郵件');
            $table->string('phone', 10)->nullable()->comment('聯絡電話');
            $table->string('department', 50)->nullable()->comment('科系');
            $table->boolean('is_active')->default(true)->comment('1=正常, 0=停權');
            $table->timestamps();

            $table->unique('identity_code', 'uk_borrowers_identity_code');
            $table->index('email', 'idx_borrowers_email');

            $table->comment('借用人資料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};
