<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Services\BookingSlotLockService;

class BookingRejectionService
{
    public function __construct(private readonly BookingSlotLockService $bookingSlotLockService)
    {
    }

    /**
     * @param array<int,int> $bookingIds
     * @param array<int,string> $allowedStatuses
     */
    public function rejectBookingsByIds(array $bookingIds, int $managerId, array $allowedStatuses): int
    {
        $normalizedIds = collect($bookingIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return 0;
        }

        $bookings = Booking::with('bookingDates.timeSlots')
            ->whereIn('id', $normalizedIds->all())
            ->whereIn('status_enum', $allowedStatuses)
            ->lockForUpdate()
            ->get();

        foreach ($bookings as $booking) {
            $booking->status_enum = Booking::STATUS_REJECTED;
            $booking->level = Booking::levelForStatus(Booking::STATUS_REJECTED);
            $booking->rejected_by = $managerId > 0 ? $managerId : null;
            $booking->rejected_at = now();
            $booking->approved_by = null;
            $booking->approved_at = null;
            $booking->save();

            $this->bookingSlotLockService->syncForBooking($booking);
        }

        return $bookings->count();
    }
}