<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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
     * Create a new message instance.
     */
    public function __construct(Booking $booking, array $timeSlots)
    {
        $this->booking = $booking;
        $this->timeSlots = $timeSlots;
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
