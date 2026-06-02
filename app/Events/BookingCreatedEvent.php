<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreatedEvent
{
    use Dispatchable, SerializesModels;

    public int $bookingId;

    /**
     * @var array<int, string>
     */
    public array $timeSlotNames;

    /**
     * @param array<int, string> $timeSlotNames
     */
    public function __construct(int $bookingId, array $timeSlotNames)
    {
        $this->bookingId = $bookingId;
        $this->timeSlotNames = $timeSlotNames;
    }
}
