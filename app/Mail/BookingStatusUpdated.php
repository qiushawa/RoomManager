<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdated extends Mailable
{
    use SerializesModels;

    public Booking $booking;

    /**
     * @var array<string, mixed>
     */
    public array $dateSummary;

    /**
     * @var array<int, array{date:string,slot_summary:string,slots:array<int,array{name:string,start_time:string,end_time:string}>}>
     */
    public array $dateSchedules;

    public int $totalSlotCount;

    public string $statusLabel;

    public string $statusColor;

    public function __construct(Booking $booking)
    {
        $booking->loadMissing(['borrower', 'classroom', 'bookingDates.timeSlots']);

        $this->booking = $booking;
        $this->statusLabel = $this->resolveStatusLabel($booking->status_enum);
        $this->statusColor = $this->resolveStatusColor($booking->status_enum);
        $this->dateSummary = $booking->getDateSummaryData('Y年m月d日', true);
        $this->dateSchedules = $booking->getDateSlotSchedules('Y年m月d日');
        $this->totalSlotCount = collect($this->dateSchedules)
            ->sum(fn (array $schedule) => count($schedule['slots']));
    }

    protected function resolveStatusLabel(?string $statusEnum): string
    {
        return match ($statusEnum) {
            Booking::STATUS_APPROVED => '已通過',
            Booking::STATUS_REJECTED => '未通過',
            Booking::STATUS_CANCELLED => '已取消',
            default => '狀態更新',
        };
    }

    protected function resolveStatusColor(?string $statusEnum): string
    {
        return match ($statusEnum) {
            Booking::STATUS_APPROVED => '#047857',
            Booking::STATUS_REJECTED => '#B91C1C',
            Booking::STATUS_CANCELLED => '#6B7280',
            default => '#1F2937',
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('【教室借用系統】您的借用申請%s', $this->statusLabel),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.status-updated',
            with: [
                'booking' => $this->booking,
                'statusLabel' => $this->statusLabel,
                'statusColor' => $this->statusColor,
                'dateSummary' => $this->dateSummary,
                'dateSchedules' => $this->dateSchedules,
                'totalSlotCount' => $this->totalSlotCount,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
