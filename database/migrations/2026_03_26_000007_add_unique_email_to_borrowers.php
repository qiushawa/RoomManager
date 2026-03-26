<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateEmails = DB::table('borrowers')
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        if ($duplicateEmails->isNotEmpty()) {
            $sample = $duplicateEmails
                ->map(fn ($row) => sprintf('%s (%d)', (string) $row->email, (int) $row->cnt))
                ->implode(', ');

            throw new RuntimeException('Cannot add uk_borrowers_email. Duplicate emails found: ' . $sample);
        }

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropIndex('idx_borrowers_email');
            $table->unique('email', 'uk_borrowers_email');
        });
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropUnique('uk_borrowers_email');
            $table->index('email', 'idx_borrowers_email');
        });
    }
};
