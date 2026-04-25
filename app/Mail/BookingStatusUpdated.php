<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public array $timeSlotDetails;

    public string $statusLabel;

    public string $statusColor;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->statusLabel = $this->resolveStatusLabel($booking->status_enum);
        $this->statusColor = $this->resolveStatusColor($booking->status_enum);

        $this->timeSlotDetails = $booking->bookingDates
            ->flatMap(fn ($bookingDate) => $bookingDate->timeSlots)
            ->unique('id')
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
                'timeSlotDetails' => $this->timeSlotDetails,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}