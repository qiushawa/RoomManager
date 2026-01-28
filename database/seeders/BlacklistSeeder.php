<?php

namespace Database\Seeders;

use App\Models\Blacklist;
use App\Models\BlacklistDetail;
use App\Models\BlacklistReason;
use Illuminate\Database\Seeder;

class BlacklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (BlacklistReason::count() === 0) {
            $this->call(BlacklistReasonSeeder::class);
        }

        $reasons = BlacklistReason::all();
        Blacklist::factory(10)->create()->each(function ($blacklist) use ($reasons) {
            $count = min($reasons->count(), rand(1, 3));
            $selectedReasons = $reasons->shuffle()->take($count);

            foreach ($selectedReasons as $reason) {
                BlacklistDetail::factory()->create([
                    'blacklist_id' => $blacklist->id,
                    'reason_id' => $reason->id,
                ]);
            }
        });
    }
}
