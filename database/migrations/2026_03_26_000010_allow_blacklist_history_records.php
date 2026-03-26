<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklists', function (Blueprint $table) {
            $table->dropUnique('uk_blacklists_borrower_id');
        });
    }

    public function down(): void
    {
        Schema::table('blacklists', function (Blueprint $table) {
            $table->unique('borrower_id', 'uk_blacklists_borrower_id');
        });
    }
};
