<?php

namespace App\Providers;

use App\Events\BookingCreatedEvent;
use App\Events\BookingStatusUpdatedEvent;
use App\Listeners\SendBookingCreatedEmailListener;
use App\Listeners\SendBookingStatusUpdatedEmailListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        BookingCreatedEvent::class => [
            SendBookingCreatedEmailListener::class,
        ],
        BookingStatusUpdatedEvent::class => [
            SendBookingStatusUpdatedEmailListener::class,
        ],
    ];
}
