<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('managers')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('managers')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('rejected_by');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->enum('status_enum', ['pending', 'approved', 'rejected', 'cancelled'])
                ->default('pending')
                ->after('status');
        });

        DB::statement("UPDATE bookings SET status_enum = CASE status WHEN 1 THEN 'approved' WHEN 2 THEN 'rejected' WHEN 3 THEN 'cancelled' ELSE 'pending' END");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('status_enum');
            $table->dropColumn('approved_at');
            $table->dropColumn('rejected_at');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
        });
    }
};
