<?php

namespace App\Listeners;

use App\Events\BookingCreatedEvent;
use App\Mail\BookingSubmitted;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class SendBookingCreatedEmailListener
{
    public function handle(BookingCreatedEvent $event): void
    {
        $booking = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->find($event->bookingId);

        if (! $booking || empty($booking->borrower?->email)) {
            return;
        }

        $timeSlotNames = $event->timeSlotNames;
        if (empty($timeSlotNames)) {
            $timeSlotNames = $booking->bookingDates
                ->flatMap(fn ($bookingDate) => $bookingDate->timeSlots)
                ->unique('id')
                ->sortBy('start_time')
                ->pluck('name')
                ->values()
                ->all();
        }

        Mail::to($booking->borrower->email)
            ->queue(new BookingSubmitted($booking, $timeSlotNames));
    }
}
