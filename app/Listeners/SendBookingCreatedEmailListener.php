<?php

namespace App\Listeners;

use App\Events\BookingCreatedEvent;
use App\Mail\BookingSubmitted;
use App\Mail\AdminBookingSubmitted;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class SendBookingCreatedEmailListener
{
    public function handle(BookingCreatedEvent $event): void
    {
        $booking = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->find($event->bookingId);

        if (! $booking) {
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

        if (! empty($booking->borrower?->email)) {
            Mail::to($booking->borrower->email)
                ->queue(new BookingSubmitted($booking, $timeSlotNames));
        }

        $adminEmail = env('MAIL_TO');
        if (! empty($adminEmail)) {
            Mail::to($adminEmail)
                ->queue(new AdminBookingSubmitted($booking, $timeSlotNames));
        }
    }
}
