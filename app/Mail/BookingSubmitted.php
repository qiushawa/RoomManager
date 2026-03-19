<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class BookingSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * 預約資料
     */
    public Booking $booking;

    /**
     * 借用時段名稱
     */
    public array $timeSlots;

    /**
     * 借用時段明細
     */
    public array $timeSlotDetails;

    /**
     * 取消申請連結
     */
    public ?string $cancelUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, array $timeSlots)
    {
        $this->booking = $booking;
        $this->timeSlots = $timeSlots;
        $this->cancelUrl = $booking->exists
            ? URL::temporarySignedRoute('bookings.cancel.confirm', now()->addDays(7), ['booking' => $booking->getKey()])
            : null;
        $this->timeSlotDetails = $booking->timeSlots
            ->sortBy('start_time')
            ->values()
            ->map(function ($timeSlot, int $index) {
                return [
                    'sequence' => $index + 1,
                    'name' => $timeSlot->name,
                    'start_time' => $this->formatTime($timeSlot->start_time),
                    'end_time' => $this->formatTime($timeSlot->end_time),
                ];
            })
            ->all();
    }

    protected function formatTime(?string $time): string
    {
        if (! $time) {
            return '-';
        }

        return substr($time, 0, 5);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【教室借用系統】您的借用申請已送出',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.submitted',
            with: [
                'booking' => $this->booking,
                'timeSlots' => $this->timeSlots,
                'timeSlotDetails' => $this->timeSlotDetails,
                'cancelUrl' => $this->cancelUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
