<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingSlotLockService
{
    /**
     * 同步單一 booking 的鎖資料。
     * pending/approved 會重建鎖，其餘狀態會移除鎖。
     */
    public function syncForBooking(Booking $booking): void
    {
        $bookingId = (int) $booking->id;
        if ($bookingId <= 0) {
            return;
        }

        if (!in_array($booking->status_enum, ['pending', 'approved'], true)) {
            $this->deleteByBookingId($bookingId);
            return;
        }

        $booking->loadMissing(['bookingDates.timeSlots']);

        $rows = [];
        $now = now();

        foreach ($booking->bookingDates as $bookingDate) {
            $date = $bookingDate->date?->format('Y-m-d');
            if (!$date) {
                continue;
            }

            foreach ($bookingDate->timeSlots as $timeSlot) {
                $rows[] = [
                    'booking_id' => $bookingId,
                    'booking_date_id' => (int) $bookingDate->id,
                    'time_slot_id' => (int) $timeSlot->id,
                    'classroom_id' => (int) $booking->classroom_id,
                    'date' => $date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->deleteByBookingId($bookingId);

        if (empty($rows)) {
            return;
        }

        DB::table('booking_slot_locks')->insert($rows);
    }

    public function deleteByBookingId(int $bookingId): void
    {
        DB::table('booking_slot_locks')
            ->where('booking_id', $bookingId)
            ->delete();
    }
}
