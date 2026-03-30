<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\TimeSlot;
use App\Services\BookingSlotLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminBookingController extends Controller
{
    public function bookings(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->addSelect([
                'first_booking_date' => BookingDate::query()
                    ->select('date')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->orderBy('date')
                    ->limit(1),
            ]);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusEnum = $this->resolveStatusEnumFromFilter($request->input('status'));
            if ($statusEnum) {
                $query->where('status_enum', $statusEnum);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('classroom', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('borrower', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderByRaw("CASE WHEN status_enum = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('first_booking_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($booking) => $this->formatBooking($booking));

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label']);

        return Inertia::render('Admin/Bookings', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'search']),
            'periods' => $periods,
        ]);
    }

    public function reviews(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->where('status_enum', 'pending');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('classroom', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('borrower', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($booking) => $this->formatBooking($booking));

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label']);

        return Inertia::render('Admin/ReviewList', [
            'bookings' => $bookings,
            'filters' => $request->only(['search']),
            'periods' => $periods,
        ]);
    }

    public function borrowingRecords(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->where('status_enum', '!=', 'pending')
            ->addSelect([
                'first_booking_date' => BookingDate::query()
                    ->select('date')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->orderBy('date')
                    ->limit(1),
            ]);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusEnum = $this->resolveStatusEnumFromFilter($request->input('status'));
            if ($statusEnum) {
                $query->where('status_enum', $statusEnum);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('classroom', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('borrower', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('first_booking_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($booking) => $this->formatBooking($booking));

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label']);

        return Inertia::render('Admin/BorrowingRecords', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'search']),
            'periods' => $periods,
        ]);
    }

    public function updateBookingStatus(Request $request, Booking $booking, BookingSlotLockService $bookingSlotLockService)
    {
        $request->validate([
            'status' => 'required|integer|in:1,2,3',
        ]);

        $nextStatus = (int) $request->input('status');
        $managerId = auth()->guard('admin')->id();
        $nextStatusEnum = Booking::enumFromLegacyStatus($nextStatus);

        $payload = [
            'status_enum' => $nextStatusEnum,
            'level' => Booking::levelForStatus($nextStatusEnum),
        ];

        if ($nextStatusEnum === Booking::STATUS_APPROVED) {
            $payload['approved_by'] = $managerId;
            $payload['approved_at'] = now();
            $payload['rejected_by'] = null;
            $payload['rejected_at'] = null;
        } elseif ($nextStatusEnum === Booking::STATUS_REJECTED) {
            $payload['rejected_by'] = $managerId;
            $payload['rejected_at'] = now();
            $payload['approved_by'] = null;
            $payload['approved_at'] = null;
        } elseif ($nextStatusEnum === Booking::STATUS_CANCELLED) {
            $payload['approved_by'] = null;
            $payload['approved_at'] = null;
            $payload['rejected_by'] = null;
            $payload['rejected_at'] = null;
        }

        DB::transaction(function () use ($booking, $payload, $bookingSlotLockService) {
            $booking->update($payload);
            $booking->refresh();
            $bookingSlotLockService->syncForBooking($booking);
        });

        return back()->with('success', '預約狀態已更新。');
    }

    public function notifications()
    {
        $pending = Booking::with(['borrower', 'classroom', 'bookingDates.timeSlots'])
            ->where('status_enum', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($b) {
                $summary = $b->getDateSummaryData('Y-m-d');

                return [
                    'id' => $b->id,
                    'date' => $summary['first_date'],
                    'date_summary' => $summary['summary'],
                    'is_multi_day' => $summary['is_multi_day'],
                    'created_at' => $b->created_at->diffForHumans(),
                    'borrower_name' => $b->borrower?->name,
                    'classroom_code' => $b->classroom?->code,
                    'time_slots' => $b->bookingDates
                        ->flatMap(fn ($bookingDate) => $bookingDate->timeSlots)
                        ->unique('id')
                        ->sortBy('start_time')
                        ->pluck('name')
                        ->values()
                        ->all(),
                ];
            });

        return response()->json([
            'count' => Booking::where('status_enum', Booking::STATUS_PENDING)->count(),
            'items' => $pending,
        ]);
    }

    private function resolveStatusEnumFromFilter(mixed $rawStatus): ?string
    {
        return Booking::enumFromFilterValue($rawStatus);
    }

    private function formatBooking(Booking $booking): array
    {
        $summary = $booking->getDateSummaryData('Y-m-d');
        $bookingDateItems = $booking->bookingDates
            ->map(function ($bookingDate) {
                return [
                    'date' => $bookingDate->date?->format('Y-m-d') ?? null,
                    'time_slots' => $bookingDate->timeSlots->pluck('name')->values()->all(),
                ];
            })
            ->filter(fn ($item) => ! empty($item['date']))
            ->values();

        return [
            'id' => $booking->id,
            'date' => $summary['first_date'],
            'date_summary' => $summary['summary'],
            'is_multi_day' => $summary['is_multi_day'],
            'status' => Booking::intFromStatusEnum($booking->status_enum),
            'status_enum' => $booking->status_enum,
            'reason' => $booking->reason,
            'teacher' => $booking->teacher,
            'created_at' => $booking->created_at->format('Y-m-d H:i'),
            'borrower' => $booking->borrower ? [
                'name' => $booking->borrower->name,
                'identity_code' => $booking->borrower->identity_code,
                'department' => $booking->borrower->department,
                'email' => $booking->borrower->email,
                'phone' => $booking->borrower->phone,
            ] : null,
            'classroom' => $booking->classroom ? [
                'code' => $booking->classroom->code,
                'name' => $booking->classroom->name,
            ] : null,
            'time_slots' => $booking->bookingDates
                ->flatMap(fn ($bookingDate) => $bookingDate->timeSlots)
                ->unique('id')
                ->sortBy('start_time')
                ->pluck('name')
                ->values()
                ->all(),
            'booking_dates' => $bookingDateItems->all(),
        ];
    }
}
