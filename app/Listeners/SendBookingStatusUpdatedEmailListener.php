<?php

namespace App\Listeners;

use App\Events\BookingStatusUpdatedEvent;
use App\Mail\BookingStatusUpdated;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class SendBookingStatusUpdatedEmailListener
{
    public function handle(BookingStatusUpdatedEvent $event): void
    {
        $booking = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->find($event->bookingId);

        if (! $booking || empty($booking->borrower?->email)) {
            return;
        }

        Mail::to($booking->borrower->email)
            ->queue(new BookingStatusUpdated($booking));
    }
}
