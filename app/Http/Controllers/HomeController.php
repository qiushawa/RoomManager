<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Booking;
use App\Models\Borrower;
use App\Services\RoomAvailabilityService;
use App\Mail\BookingSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
        $buildings = [
            ['name' => '綜三館 BGC', 'rooms' => Classroom::where('code', 'like', 'BGC%')->get(['id', 'name', 'code'])],
            ['name' => '跨領域 BCB', 'rooms' => Classroom::where('code', 'like', 'BCB%')->get(['id', 'name', 'code'])],
        ];

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label', 'start_time', 'end_time']);

        // 2. 接收參數
        $dateParam = $request->input('date', now()->format('Y-m-d'));
        $targetRoomCode = $request->input('room_code');

        // 從 flash session 讀取高亮資訊 (用於轉址後顯示剛申請的時段)
        $highlight = session('highlight');

        $baseDate = Carbon::parse($dateParam);
        $startDate = $baseDate->copy()->startOfWeek(CarbonInterface::SUNDAY);
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
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->whereNotIn('status', [2, 3]);
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
            'classroom_code' => 'required|string', // 新增教室 code 驗證
            'date' => 'required|date',
            'start_slot_id' => 'required|exists:time_slots,id',
            'end_slot_id' => 'required|exists:time_slots,id',
            'applicant.name' => 'required|string|max:255',
            'applicant.identity_code' => 'required|string|max:50', // 必填以確保借用者身份識別
            'applicant.email' => 'required|email|max:255', // 必填以發送確認郵件
            'applicant.phone' => 'nullable|string|max:20',
            'applicant.department' => 'nullable|string|max:255',
            'applicant.teacher' => 'nullable|string|max:255',
            'applicant.reason' => 'nullable|string|max:1000',
        ]);

        // 使用 identity_code + email 組合查詢借用者，避免身份混淆
        $applicantData = $requestData['applicant'];
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
        // 建立預約
        $booking = new Booking();
        $booking->classroom_id = $requestData['classroom_id'];
        $booking->borrower_id = $borrower->id;
        $booking->date = $requestData['date'];
        $booking->start_slot_id = $requestData['start_slot_id'];
        $booking->end_slot_id = $requestData['end_slot_id'];
        $booking->reason = $applicantData['reason'] ?? null;
        $booking->teacher = $applicantData['teacher'] ?? null;
        $booking->status = 0; // 預設為待審核
        $booking->save();

        // 轉址回日曆表頁面，帶上教室 code 和日期
        $roomCode = $requestData['classroom_code'];
        $date = $requestData['date'];

        // 使用 flash session 傳遞高亮資訊
        $startSlot = TimeSlot::find($requestData['start_slot_id']);
        $endSlot = TimeSlot::find($requestData['end_slot_id']);
        $slots = TimeSlot::whereBetween('start_time', [$startSlot->start_time, $endSlot->start_time])
            ->orderBy('start_time')
            ->pluck('name')
            ->toArray();

        // 發送確認郵件給申請人
        $booking->load(['classroom', 'borrower', 'startSlot', 'endSlot']);
        if ($borrower->email) {
            Mail::to($borrower->email)->send(new BookingSubmitted($booking, $slots));
        }

        return redirect("/Home?date=" . $date . "&room_code=" . $roomCode)
            ->with('success', '預約已成功提交！')
            ->with('highlight', [
                'date' => $date,
                'slots' => $slots
            ]);
}

}
