<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminBookingSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Booking $booking;

    /**
     * @var array<int, string>
     */
    public array $timeSlots;

    /**
     * @var array<string, mixed>
     */
    public array $dateSummary;

    /**
     * @var array<int, array{date:string,slot_summary:string,slots:array<int,array{name:string,start_time:string,end_time:string}>}>
     */
    public array $dateSchedules;

    public int $totalSlotCount;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, array $timeSlots = [])
    {
        $booking->loadMissing(['borrower', 'classroom', 'bookingDates.timeSlots']);

        $this->booking = $booking;
        $this->timeSlots = $timeSlots;
        $this->dateSummary = $booking->getDateSummaryData('Y年m月d日', true);
        $this->dateSchedules = $booking->getDateSlotSchedules('Y年m月d日');
        $this->totalSlotCount = collect($this->dateSchedules)
            ->sum(fn (array $schedule) => count($schedule['slots']));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【系統通知】有新的教室借用申請',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.booking-submitted',
            with: [
                'booking' => $this->booking,
                'timeSlots' => $this->timeSlots,
                'dateSummary' => $this->dateSummary,
                'dateSchedules' => $this->dateSchedules,
                'totalSlotCount' => $this->totalSlotCount,
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
