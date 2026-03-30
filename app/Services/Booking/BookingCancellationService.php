<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Services\BookingSlotLockService;

class BookingCancellationService
{
    public function __construct(private readonly BookingSlotLockService $bookingSlotLockService)
    {
    }

    public function findBookingForCancellation(string $bookingId): ?Booking
    {
        return Booking::with(['classroom', 'borrower', 'bookingDates.timeSlots'])
            ->find($bookingId);
    }

    /**
     * @return array<string,mixed>
     */
    public function formatBookingSummary(Booking $booking): array
    {
        $booking->loadMissing(['bookingDates.timeSlots']);
        $dateSummary = $booking->getDateSummaryData('Y年m月d日', true)['summary'];

        return [
            'borrower_name' => $booking->borrower?->name ?? '未提供',
            'classroom_name' => trim(($booking->classroom?->code ?? '').' '.($booking->classroom?->name ?? '')),
            'date' => $dateSummary,
            'teacher' => $booking->teacher ?: '未填寫',
            'reason' => $booking->reason ?: '未填寫',
            'time_slots' => $booking->bookingDates
                ->flatMap(fn ($bookingDate) => $bookingDate->timeSlots)
                ->unique('id')
                ->sortBy('start_time')
                ->map(fn ($timeSlot) => sprintf('%s (%s-%s)', $timeSlot->name, substr((string) $timeSlot->start_time, 0, 5), substr((string) $timeSlot->end_time, 0, 5)))
                ->values()
                ->all(),
        ];
    }

    public function cancelPendingBooking(Booking $booking): void
    {
        $booking->status_enum = Booking::STATUS_CANCELLED;
        $booking->level = Booking::levelForStatus(Booking::STATUS_CANCELLED);
        $booking->save();

        $this->bookingSlotLockService->syncForBooking($booking);
    }
}
