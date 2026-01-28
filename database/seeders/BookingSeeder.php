<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $borrowers = Borrower::all();
        $classrooms = Classroom::where('is_active', true)->get();
        $timeSlots = TimeSlot::where('name', '!=', '午休')->get();

        if ($borrowers->isEmpty() || $classrooms->isEmpty() || $timeSlots->isEmpty()) {
            return;
        }

        // 產生 20 筆預約
        for ($i = 0; $i < 20; $i++) {
            $slot = $timeSlots->random();
            
            Booking::factory()->create([
                'borrower_id' => $borrowers->random()->id,
                'classroom_id' => $classrooms->random()->id,
                'start_slot_id' => $slot->id,
                'end_slot_id' => $slot->id, // 簡化：單節借用
                'date' => now()->addDays(rand(-5, 10)), // 過去或未來幾天
            ]);
        }
    }
}
