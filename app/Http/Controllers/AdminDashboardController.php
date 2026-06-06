<?php

namespace App\Http\Controllers;

use App\Models\BlacklistDetail;
use App\Models\BlacklistReason;
use App\Models\Booking;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $currentSemester = Semester::findByDate(now());

        $bookingsPerRoom = collect();
        if ($currentSemester) {
            $bookingsPerRoom = DB::table('booking_dates')
                ->join('bookings', 'booking_dates.booking_id', '=', 'bookings.id')
                ->whereBetween('booking_dates.date', [
                    $currentSemester->start_date->format('Y-m-d'),
                    $currentSemester->end_date->format('Y-m-d'),
                ])
                ->whereIn('bookings.status_enum', Booking::activeStatusEnums())
                ->selectRaw('bookings.classroom_id, count(DISTINCT bookings.id) as count')
                ->groupBy('bookings.classroom_id')
                ->pluck('count', 'bookings.classroom_id');
        }

        $classrooms = Classroom::activeClassrooms();
        $bookingsPerRoomChart = [
            'labels' => $classrooms->pluck('code')->toArray(),
            'data' => $classrooms->map(fn ($r) => $bookingsPerRoom->get($r->id, 0))->toArray(),
        ];

        $reasons = BlacklistReason::withCount('blacklistDetails')->get();
        $reasonChart = [
            'labels' => $reasons->pluck('reason')->toArray(),
            'data' => $reasons->pluck('blacklist_details_count')->toArray(),
        ];

        $problemsPerRoomData = collect();
        if ($currentSemester) {
            $problemsPerRoomData = BlacklistDetail::join('blacklists', 'blacklist_details.blacklist_id', '=', 'blacklists.id')
                ->join('borrowers', 'blacklists.borrower_id', '=', 'borrowers.id')
                ->join('bookings', 'bookings.borrower_id', '=', 'borrowers.id')
                ->join('booking_dates', function ($join) use ($currentSemester) {
                    $join->on('booking_dates.booking_id', '=', 'bookings.id')
                        ->whereBetween('booking_dates.date', [
                            $currentSemester->start_date->format('Y-m-d'),
                            $currentSemester->end_date->format('Y-m-d'),
                        ]);
                })
                ->selectRaw('bookings.classroom_id, count(DISTINCT blacklist_details.id) as count')
                ->groupBy('bookings.classroom_id')
                ->pluck('count', 'bookings.classroom_id');
        }

        $problemsPerRoomChart = [
            'labels' => $classrooms->pluck('code')->toArray(),
            'data' => $classrooms->map(fn ($r) => $problemsPerRoomData->get($r->id, 0))->toArray(),
        ];

        $slotPopularity = collect();
        if ($currentSemester) {
            $slotPopularity = DB::table('booking_date_time_slot')
                ->join('booking_dates', 'booking_date_time_slot.booking_date_id', '=', 'booking_dates.id')
                ->join('bookings', 'booking_dates.booking_id', '=', 'bookings.id')
                ->whereBetween('booking_dates.date', [
                    $currentSemester->start_date->format('Y-m-d'),
                    $currentSemester->end_date->format('Y-m-d'),
                ])
                ->whereIn('bookings.status_enum', Booking::activeStatusEnums())
                ->selectRaw('booking_date_time_slot.time_slot_id, count(*) as count')
                ->groupBy('booking_date_time_slot.time_slot_id')
                ->pluck('count', 'booking_date_time_slot.time_slot_id');
        }

        $timeSlots = TimeSlot::orderBy('start_time')->get();
        $slotPopularityChart = [
            'labels' => $timeSlots->pluck('name')->toArray(),
            'data' => $timeSlots->map(fn ($s) => $slotPopularity->get($s->id, 0))->toArray(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'activeClassroomsCount' => $classrooms->count(),
            'totalBookingsCount' => Booking::count(),
            'pendingBookingsCount' => Booking::pending()->count(),
            'currentSemester' => $currentSemester ? $currentSemester->display_name : null,
            'bookingsPerRoomChart' => $bookingsPerRoomChart,
            'reasonChart' => $reasonChart,
            'problemsPerRoomChart' => $problemsPerRoomChart,
            'slotPopularityChart' => $slotPopularityChart,
        ]);
    }
}
