<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Blacklist;
use App\Services\RoomAvailabilityService;
use App\Mail\BookingSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Carbon\Carbon;
use Carbon\CarbonInterface;
class HomeController extends Controller
{
    protected $availabilityService;

    public function __construct(RoomAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
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
                    ->whereNotIn('status', [2, 3])
                    ->where(function ($bookingQuery) use ($startDate, $endDate) {
                        $bookingQuery
                            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                            ->orWhereHas('bookingDates', function ($dateQuery) use ($startDate, $endDate) {
                                $dateQuery->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                            });
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

    public function store(Request $request)
    {
        $requestData = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'classroom_code' => 'required|string',
            'date' => 'nullable|date|required_without:selections',
            'time_slot_ids' => 'nullable|array|min:1|required_with:date',
            'time_slot_ids.*' => 'exists:time_slots,id',
            'selections' => 'nullable|array|min:1|required_without:date',
            'selections.*.date' => 'required|date',
            'selections.*.time_slot_ids' => 'required|array|min:1',
            'selections.*.time_slot_ids.*' => 'exists:time_slots,id',
            'applicant.name' => 'required|string|max:50',
            'applicant.identity_code' => ['required', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
            'applicant.email' => 'required|email|max:255',
            'applicant.phone' => 'nullable|string|max:10',
            'applicant.department' => 'nullable|string|max:50',
            'applicant.teacher' => 'nullable|string|max:50',
            'applicant.reason' => 'nullable|string|max:255',
        ], [
            'applicant.identity_code.regex' => '學號/員工編號僅可輸入英文與數字。',
        ]);

        $applicantData = $requestData['applicant'];

        $activeBlacklist = Blacklist::findActiveByIdentityCode($applicantData['identity_code']);

        if ($activeBlacklist) {
            return back()->withErrors([
                'applicant.identity_code' => '此學號目前停權中，停權至 ' . $activeBlacklist->banned_until->format('Y-m-d') . '。',
            ]);
        }

        $borrower = Borrower::firstOrCreate(
            [
                'identity_code' => $applicantData['identity_code'],
                'email' => $applicantData['email'],
            ],
            [
                'name' => $applicantData['name'],
                'phone' => $applicantData['phone'] ?? null,
                'department' => $applicantData['department'] ?? null,
            ]
        );

        $selectionRows = collect($requestData['selections'] ?? [
            [
                'date' => $requestData['date'] ?? null,
                'time_slot_ids' => $requestData['time_slot_ids'] ?? [],
            ],
        ])
            ->filter(fn ($item) => is_array($item) && !empty($item['date']) && !empty($item['time_slot_ids']))
            ->map(function ($item) {
                $slotIds = collect($item['time_slot_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'date' => (string) $item['date'],
                    'time_slot_ids' => $slotIds,
                ];
            })
            ->filter(fn ($item) => !empty($item['time_slot_ids']))
            ->groupBy('date')
            ->map(function ($items, $date) {
                $mergedSlotIds = collect($items)
                    ->flatMap(fn ($item) => $item['time_slot_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'date' => (string) $date,
                    'time_slot_ids' => $mergedSlotIds,
                ];
            })
            ->values();

        if ($selectionRows->isEmpty()) {
            return back()->withErrors([
                'selections' => '請至少選擇一筆借用日期與時段。',
            ]);
        }

        $firstSelection = $selectionRows->first();

        $booking = DB::transaction(function () use ($requestData, $borrower, $applicantData, $selectionRows, $firstSelection) {
            $booking = new Booking();
            $booking->classroom_id = $requestData['classroom_id'];
            $booking->borrower_id = $borrower->id;
            // 保留主表 date 供舊流程與報表相容，值為最早借用日
            $booking->date = $firstSelection['date'];
            $booking->reason = $applicantData['reason'] ?? null;
            $booking->teacher = $applicantData['teacher'] ?? null;
            $booking->status = 0;
            $booking->save();

            foreach ($selectionRows as $selection) {
                $bookingDate = $booking->bookingDates()->create([
                    'date' => $selection['date'],
                ]);
                $bookingDate->timeSlots()->sync($selection['time_slot_ids']);
            }

            // 保留既有 booking_time_slot 關聯，使用首日時段以維持既有摘要/通知格式。
            $booking->timeSlots()->sync($firstSelection['time_slot_ids']);
            $booking->load(['classroom', 'borrower', 'timeSlots', 'bookingDates.timeSlots']);

            return $booking;
        });

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

    public function showCancelConfirmation(Request $request, string $booking)
    {
        $bookingModel = $this->findBookingForCancellation($booking);

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
            'state' => $bookingModel->status === 0 ? 'confirm' : ($bookingModel->status === 3 ? 'cancelled' : 'locked'),
            'summary' => $this->formatBookingSummary($bookingModel),
            'cancelActionUrl' => URL::signedRoute('bookings.cancel.destroy', ['booking' => $bookingModel->id]),
            'homeUrl' => route('home.index'),
        ]);
    }

    public function destroy(Request $request, string $booking)
    {
        $bookingModel = $this->findBookingForCancellation($booking);

        if (! $bookingModel) {
            return Inertia::render('BookingCancellation', [
                'mode' => 'result',
                'state' => 'missing',
                'summary' => null,
                'cancelActionUrl' => null,
                'homeUrl' => route('home.index'),
            ]);
        }

        $summary = $this->formatBookingSummary($bookingModel);

        if ($bookingModel->status !== 0) {
            return Inertia::render('BookingCancellation', [
                'mode' => 'result',
                'state' => $bookingModel->status === 3 ? 'cancelled' : 'locked',
                'summary' => $summary,
                'cancelActionUrl' => null,
                'homeUrl' => route('home.index'),
            ]);
        }

        $bookingModel->status = 3;
        $bookingModel->save();

        return Inertia::render('BookingCancellation', [
            'mode' => 'result',
            'state' => 'cancelled',
            'summary' => $summary,
            'cancelActionUrl' => null,
            'homeUrl' => route('home.index'),
        ]);
    }

    protected function findBookingForCancellation(string $bookingId): ?Booking
    {
        return Booking::with(['classroom', 'borrower', 'timeSlots'])
            ->find($bookingId);
    }

    protected function formatBookingSummary(Booking $booking): array
    {
        $booking->loadMissing(['bookingDates.timeSlots']);

        $dateSummary = Carbon::parse($booking->date)->format('Y年m月d日');
        if ($booking->bookingDates->isNotEmpty()) {
            $sortedDates = $booking->bookingDates
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date))
                ->sortBy(fn ($date) => $date->timestamp)
                ->values();

            $firstDate = $sortedDates->first();
            $lastDate = $sortedDates->last();

            if ($firstDate && $lastDate && !$firstDate->isSameDay($lastDate)) {
                $dateSummary = sprintf(
                    '%s ~ %s（共 %d 天）',
                    $firstDate->format('Y年m月d日'),
                    $lastDate->format('Y年m月d日'),
                    $sortedDates->count()
                );
            } elseif ($firstDate) {
                $dateSummary = $firstDate->format('Y年m月d日');
            }
        }

        return [
            'borrower_name' => $booking->borrower?->name ?? '未提供',
            'classroom_name' => trim(($booking->classroom?->code ?? '') . ' ' . ($booking->classroom?->name ?? '')),
            'date' => $dateSummary,
            'teacher' => $booking->teacher ?: '未填寫',
            'reason' => $booking->reason ?: '未填寫',
            'time_slots' => $booking->timeSlots
                ->sortBy('start_time')
                ->map(fn ($timeSlot) => sprintf('%s (%s-%s)', $timeSlot->name, substr((string) $timeSlot->start_time, 0, 5), substr((string) $timeSlot->end_time, 0, 5)))
                ->values()
                ->all(),
        ];
    }

}
