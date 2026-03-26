<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_schedule_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_schedule_id')->constrained('course_schedules')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();

            $table->unique(['course_schedule_id', 'time_slot_id'], 'uk_course_schedule_time_slot');
            $table->index('time_slot_id', 'idx_course_schedule_time_slot_time_slot');
            $table->comment('課程排程與時段多對多關聯');
        });

        $orderedSlotIds = DB::table('time_slots')
            ->orderBy('start_time')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $slotIndexById = [];
        foreach ($orderedSlotIds as $index => $slotId) {
            $slotIndexById[$slotId] = $index;
        }

        DB::table('course_schedules')
            ->select(['id', 'start_slot_id', 'end_slot_id'])
            ->whereNotNull('start_slot_id')
            ->whereNotNull('end_slot_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($orderedSlotIds, $slotIndexById) {
                $payload = [];

                foreach ($rows as $row) {
                    $startSlotId = (int) $row->start_slot_id;
                    $endSlotId = (int) $row->end_slot_id;
                    $startIndex = $slotIndexById[$startSlotId] ?? null;
                    $endIndex = $slotIndexById[$endSlotId] ?? null;

                    if ($startIndex === null || $endIndex === null) {
                        continue;
                    }

                    $from = min($startIndex, $endIndex);
                    $to = max($startIndex, $endIndex);
                    for ($i = $from; $i <= $to; $i++) {
                        $payload[] = [
                            'course_schedule_id' => $row->id,
                            'time_slot_id' => $orderedSlotIds[$i],
                        ];
                    }
                }

                if (!empty($payload)) {
                    DB::table('course_schedule_time_slots')->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_schedule_time_slots');
    }
};
