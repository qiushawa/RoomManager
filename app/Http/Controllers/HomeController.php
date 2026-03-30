<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Booking;
use App\Services\Booking\BookingCancellationService;
use App\Services\Booking\BookingCreationService;
use App\Services\RoomAvailabilityService;
use App\Mail\BookingSubmitted;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class HomeController extends Controller
{
    protected $availabilityService;
    protected $bookingCreationService;
    protected $bookingCancellationService;

    public function __construct(
        RoomAvailabilityService $availabilityService,
        BookingCreationService $bookingCreationService,
        BookingCancellationService $bookingCancellationService,
    )
    {
        $this->availabilityService = $availabilityService;
        $this->bookingCreationService = $bookingCreationService;
        $this->bookingCancellationService = $bookingCancellationService;
    }

    public function index(Request $request)
    {
        // 1. 基礎資料 (保持不變)
        // 注意：這裡前端需要用到 code，請確保你的 Classroom 查詢有 select 'code'
        // 只獲取啟用的教室，並且只選取必要的欄位 (id, name, code)
        $buildings = [
            ['name' => '綜三館 BGC', 'rooms' => Classroom::where('code', 'like', 'BGC%')->where('is_active', true)->get(['id', 'name', 'code'])],
            ['name' => '跨領域 BCB', 'rooms' => Classroom::where('code', 'like', 'BCB%')->where('is_active', true)->get(['id', 'name', 'code'])],
            ['name' => '科研大樓 BRA', 'rooms' => Classroom::where('code', 'like', 'BRA%')->where('is_active', true)->get(['id', 'name', 'code'])],
        ];

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label', 'start_time', 'end_time']);

        // 2. 接收參數
        $dateParam = $request->input('date', now()->format('Y-m-d'));
        $targetRoomCode = $request->input('room_code');

        // 從 flash session 讀取高亮資訊 (用於轉址後顯示剛申請的時段)
        $highlight = session('highlight');

        $baseDate = Carbon::parse($dateParam);
        $startDate = $baseDate->copy()->startOfWeek(CarbonInterface::MONDAY);
        $endDate = $startDate->copy()->addDays(6);

        // 3. 批次查詢所有教室的佔用狀況
        // 收集所有教室 ID
        $allClassrooms = collect();
        foreach ($buildings as $building) {
            $allClassrooms = $allClassrooms->merge($building['rooms']);
        }

        // 轉為 Eloquent Collection 以使用 load 方法
        $allClassrooms = \Illuminate\Database\Eloquent\Collection::make($allClassrooms);

        // 預載入關聯 (減少 N+1)
        // 注意：這裡 $allClassrooms 裡的項目是 Model 實例，可以直接 load
        $allClassrooms->load([
            'bookings' => function ($query) use ($startDate, $endDate) {
                $query
                    ->whereIn('status_enum', Booking::activeStatusEnums())
                    ->whereHas('bookingDates', function ($dateQuery) use ($startDate, $endDate) {
                        $dateQuery->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                    });
            },
            'courseSchedules'
        ]);

        $allOccupiedData = $this->availabilityService->getBatchOccupiedData(
            $allClassrooms,
            $startDate,
            $endDate
        );

        return Inertia::render('Home', [
            'buildings' => $buildings,
            'periods' => $periods,
            'allOccupiedData' => $allOccupiedData, // 傳遞所有資料
            'filters' => [
                'date' => $baseDate->format('Y-m-d'),
                'room_code' => $targetRoomCode, // <--- 回傳給前端的是 code
                'highlight' => $highlight // 高亮剛申請的時段
            ]
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        $requestData = $request->validated();

        try {
            $result = $this->bookingCreationService->create($requestData);
            $booking = $result['booking'];
            $selectionRows = $result['selection_rows'];
            $borrower = $result['borrower'];
        } catch (QueryException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            if ($sqlState === '23000') {
                return back()->withErrors([
                    'selections' => '所選時段已被其他申請占用，請重新整理後再試。',
                ]);
            }

            throw $e;
        }

        $firstSelection = $selectionRows->first();

        $roomCode = $requestData['classroom_code'];
        $date = $firstSelection['date'];

        if ($borrower->email) {
            $slots = TimeSlot::whereIn('id', $firstSelection['time_slot_ids'])
                ->orderBy('start_time')
                ->pluck('name')
                ->toArray();

            Mail::to($borrower->email)->send(new BookingSubmitted($booking, $slots));
        }

        $selectionCount = $selectionRows->count();
        $successMessage = $selectionCount > 1
            ? "預約已成功提交！共 {$selectionCount} 天（單一申請）。"
            : '預約已成功提交！';

        $highlightSlots = TimeSlot::whereIn('id', $firstSelection['time_slot_ids'])
            ->orderBy('start_time')
            ->pluck('name')
            ->toArray();

        return redirect("/Home?date=" . $date . "&room_code=" . $roomCode)
            ->with('success', $successMessage)
            ->with('highlight', [
                'date' => $date,
                'slots' => $highlightSlots
            ]);
    }

    public function showCancelConfirmation(CancelBookingRequest $request, string $booking)
    {
        $bookingModel = $this->bookingCancellationService->findBookingForCancellation($booking);

        if (! $bookingModel) {
            return Inertia::render('BookingCancellation', [
                'mode' => 'confirm',
                'state' => 'missing',
                'summary' => null,
                'cancelActionUrl' => null,
                'homeUrl' => route('home.index'),
            ]);
        }

        return Inertia::render('BookingCancellation', [
            'mode' => 'confirm',
            'state' => $bookingModel->status_enum === 'pending' ? 'confirm' : ($bookingModel->status_enum === 'cancelled' ? 'cancelled' : 'locked'),
            'summary' => $this->bookingCancellationService->formatBookingSummary($bookingModel),
            'cancelActionUrl' => URL::signedRoute('bookings.cancel.destroy', ['booking' => $bookingModel->id]),
            'homeUrl' => route('home.index'),
        ]);
    }

    public function destroy(CancelBookingRequest $request, string $booking)
    {
        $bookingModel = $this->bookingCancellationService->findBookingForCancellation($booking);

        if (! $bookingModel) {
            return Inertia::render('BookingCancellation', [
                'mode' => 'result',
                'state' => 'missing',
                'summary' => null,
                'cancelActionUrl' => null,
                'homeUrl' => route('home.index'),
            ]);
        }

        $summary = $this->bookingCancellationService->formatBookingSummary($bookingModel);

        if ($bookingModel->status_enum !== 'pending') {
            return Inertia::render('BookingCancellation', [
                'mode' => 'result',
                'state' => $bookingModel->status_enum === 'cancelled' ? 'cancelled' : 'locked',
                'summary' => $summary,
                'cancelActionUrl' => null,
                'homeUrl' => route('home.index'),
            ]);
        }

        $this->bookingCancellationService->cancelPendingBooking($bookingModel);

        return Inertia::render('BookingCancellation', [
            'mode' => 'result',
            'state' => 'cancelled',
            'summary' => $summary,
            'cancelActionUrl' => null,
            'homeUrl' => route('home.index'),
        ]);
    }
}
