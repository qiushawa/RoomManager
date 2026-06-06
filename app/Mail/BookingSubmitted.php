<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class BookingSubmitted extends Mailable
{
    use SerializesModels;

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

    public ?string $cancelUrl;

    public function __construct(Booking $booking, array $timeSlots = [])
    {
        $booking->loadMissing(['borrower', 'classroom', 'bookingDates.timeSlots']);

        $this->booking = $booking;
        $this->timeSlots = $timeSlots;
        $this->dateSummary = $booking->getDateSummaryData('Y年m月d日', true);
        $this->dateSchedules = $booking->getDateSlotSchedules('Y年m月d日');
        $this->totalSlotCount = collect($this->dateSchedules)
            ->sum(fn (array $schedule) => count($schedule['slots']));
        $this->cancelUrl = $booking->exists
            ? URL::temporarySignedRoute('bookings.cancel.confirm', now()->addDays(7), ['booking' => $booking->getKey()])
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【教室借用系統】您的借用申請已送出',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.submitted',
            with: [
                'booking' => $this->booking,
                'timeSlots' => $this->timeSlots,
                'dateSummary' => $this->dateSummary,
                'dateSchedules' => $this->dateSchedules,
                'totalSlotCount' => $this->totalSlotCount,
                'cancelUrl' => $this->cancelUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
