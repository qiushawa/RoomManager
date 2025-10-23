<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blacklist_reason', function (Blueprint $table) {
            $table->tinyInteger('reason_id')->primary()->comment('事由編號');
            $table->string('reason', 10)->comment('事由內容');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist_reason');
    }
};
