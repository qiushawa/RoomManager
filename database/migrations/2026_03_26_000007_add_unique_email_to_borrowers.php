<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasLegacyEmailIndex = $this->indexExists('borrowers', 'idx_borrowers_email');

        $duplicateEmails = DB::table('borrowers')
            ->select('email')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('email')
            ->get();

        foreach ($duplicateEmails as $row) {
            $email = (string) $row->email;

            $borrowerIds = DB::table('borrowers')
                ->where('email', $email)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($borrowerIds->count() <= 1) {
                continue;
            }

            // Keep the oldest borrower email unchanged; rewrite the rest to unique aliases.
            foreach ($borrowerIds->slice(1) as $borrowerId) {
                $normalized = $this->buildUniqueAliasEmail($email, $borrowerId);

                DB::table('borrowers')
                    ->where('id', $borrowerId)
                    ->update([
                        'email' => $normalized,
                        'updated_at' => now(),
                    ]);

                Log::warning('Normalized duplicate borrower email before adding unique index.', [
                    'borrower_id' => $borrowerId,
                    'original_email' => $email,
                    'normalized_email' => $normalized,
                ]);
            }
        }

        Schema::table('borrowers', function (Blueprint $table) use ($hasLegacyEmailIndex) {
            if ($hasLegacyEmailIndex) {
                $table->dropIndex('idx_borrowers_email');
            }
            $table->unique('email', 'uk_borrowers_email');
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $tableName)
                ->where('index_name', $indexName)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function buildUniqueAliasEmail(string $email, int $borrowerId): string
    {
        $email = trim($email);

        if (str_contains($email, '@')) {
            [$local, $domain] = explode('@', $email, 2);
            $local = $local === '' ? 'user' : $local;
            $domain = $domain === '' ? 'invalid.local' : $domain;
            $candidate = "{$local}+dup{$borrowerId}@{$domain}";
        } else {
            $candidate = "{$email}.dup{$borrowerId}";
        }

        $suffix = 1;
        while (
            DB::table('borrowers')
                ->where('email', $candidate)
                ->where('id', '!=', $borrowerId)
                ->exists()
        ) {
            if (str_contains($candidate, '@')) {
                [$local, $domain] = explode('@', $candidate, 2);
                $candidate = "{$local}.{$suffix}@{$domain}";
            } else {
                $candidate = "{$candidate}.{$suffix}";
            }

            $suffix++;
        }

        return $candidate;
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropUnique('uk_borrowers_email');
            $table->index('email', 'idx_borrowers_email');
        });
    }
};
