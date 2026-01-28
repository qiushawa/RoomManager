<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\TimeSlot;
use App\Models\Building; // 假設你有 Building Model 或是寫死的結構
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;
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

        $periods = TimeSlot::orderBy('start_time')->get(['id', 'name as code', 'name as label']);

        // 2. 接收參數 (改用 room_code)
        $dateParam = $request->input('date', now()->format('Y-m-d'));
        $targetRoomCode = $request->input('room_code'); // <--- 修改這裡
        
        $baseDate = Carbon::parse($dateParam);
        $startDate = $baseDate->copy()->startOfWeek(CarbonInterface::SUNDAY); 
        $endDate = $startDate->copy()->addDays(6);

        $occupiedData = [];

        // 3. 透過 Code 查詢 ID，再計算佔用
        if ($targetRoomCode) {
            // 先找出對應的教室
            $room = Classroom::where('code', $targetRoomCode)->first();

            if ($room) {
                // Service 維持傳入 ID (因為資料庫關聯較快)
                $occupiedData = $this->availabilityService->getOccupiedData(
                    $room->id, 
                    $startDate, 
                    $endDate
                );
            }
        }

        return Inertia::render('Home', [
            'buildings' => $buildings,
            'periods' => $periods,
            'initialOccupiedData' => $occupiedData,
            'filters' => [
                'date' => $baseDate->format('Y-m-d'),
                'room_code' => $targetRoomCode // <--- 回傳給前端的是 code
            ]
        ]);
    }

    public function store(Request $request)
    {
        //todo: 處理預約提交的邏輯
    }
}