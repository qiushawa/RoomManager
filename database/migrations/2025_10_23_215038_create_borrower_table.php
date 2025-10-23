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
        Schema::create('borrower', function (Blueprint $table) {
            $table->char('student_id', 8)->primary()->comment('學生學號');
            $table->string('email', 45)->nullable()->comment('借用者Email');
            $table->string('name', 10)->nullable()->comment('借用者姓名');
            $table->char('cellphone', 10)->nullable()->comment('借用者手機');
            $table->string('department', 15)->nullable()->comment('借用者科系');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrower');
    }
};
