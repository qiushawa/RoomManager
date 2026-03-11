<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Classroom;
use App\Models\Booking;
use App\Models\Semester;
use App\Models\BlacklistDetail;
use App\Models\BlacklistReason;
use App\Models\TimeSlot;

use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * 顯示管理員登入頁面
     */
    public function login()
    {
        // 如果已經登入，直接導向儀表板
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return Inertia::render('Admin/Login');
    }

    /**
     * 處理登入請求
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (auth()->guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'username' => '提供的帳號或密碼錯誤。',
        ])->onlyInput('username');
    }

    /**
     * 處理登出請求
     */
    public function logout(Request $request)
    {
        auth()->guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * 顯示管理員控制面板總覽
     */
    public function dashboard()
    {
        $currentSemester = Semester::findByDate(now());

        // --- 圖表 1: 各教室借用次數 (本學期) ---
        $bookingsPerRoom = collect();
        if ($currentSemester) {
            $bookingsPerRoom = Booking::whereBetween('date', [
                    $currentSemester->start_date->format('Y-m-d'),
                    $currentSemester->end_date->format('Y-m-d'),
                ])
                ->selectRaw('classroom_id, count(*) as count')
                ->groupBy('classroom_id')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->classroom_id => $row->count]);
        }
        $classrooms = Classroom::activeClassrooms();
        $bookingsPerRoomChart = [
            'labels' => $classrooms->pluck('code')->toArray(),
            'data' => $classrooms->map(fn ($r) => $bookingsPerRoom->get($r->id, 0))->toArray(),
        ];

        // --- 圖表 2: 各問題發生次數比例 (全部) ---
        $reasons = BlacklistReason::withCount('blacklistDetails')->get();
        $reasonChart = [
            'labels' => $reasons->pluck('reason')->toArray(),
            'data' => $reasons->pluck('blacklist_details_count')->toArray(),
        ];

        // --- 圖表 3: 各教室問題發生次數 (本學期) ---
        $problemsPerRoomData = collect();
        if ($currentSemester) {
            $problemsPerRoomData = BlacklistDetail::join('blacklists', 'blacklist_details.blacklist_id', '=', 'blacklists.id')
                ->join('borrowers', 'blacklists.borrower_id', '=', 'borrowers.id')
                ->join('bookings', function ($join) use ($currentSemester) {
                    $join->on('bookings.borrower_id', '=', 'borrowers.id')
                        ->whereBetween('bookings.date', [
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

        // --- 圖表 4: 各時段借用熱度 (本學期) ---
        $slotPopularity = collect();
        if ($currentSemester) {
            $slotPopularity = DB::table('booking_time_slot')
                ->join('bookings', 'booking_time_slot.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.date', [
                    $currentSemester->start_date->format('Y-m-d'),
                    $currentSemester->end_date->format('Y-m-d'),
                ])
                ->selectRaw('booking_time_slot.time_slot_id, count(*) as count')
                ->groupBy('booking_time_slot.time_slot_id')
                ->pluck('count', 'booking_time_slot.time_slot_id');
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

    /**
     * 顯示預約管理
     */
    public function bookings(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'timeSlots']);

        // 篩選狀態
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', (int) $request->input('status'));
        }

        // 搜尋 (教室代碼、借用人姓名、事由)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('classroom', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('borrower', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderByRaw('CASE WHEN status = 0 THEN 0 ELSE 1 END')
            ->orderBy('date', 'desc')
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

    /**
     * 審核列表（僅待審核）
     */
    public function reviews(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'timeSlots'])
            ->where('status', 0);

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

    /**
     * 短期借用紀錄（已審核）
     */
    public function borrowingRecords(Request $request)
    {
        $query = Booking::with(['borrower', 'classroom', 'timeSlots'])
            ->where('status', '!=', 0);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('classroom', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('borrower', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('date', 'desc')
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

    /**
     * 格式化預約資料
     */
    private function formatBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'date' => $booking->date,
            'status' => $booking->status,
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
            'time_slots' => $booking->timeSlots->pluck('name')->toArray(),
        ];
    }

    /**
     * 更新預約狀態
     */
    public function updateBookingStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|integer|in:1,2,3',
        ]);

        $booking->update(['status' => $request->input('status')]);

        return back()->with('success', '預約狀態已更新。');
    }

    /**
     * 取得待審核預約通知 (JSON)
     */
    public function notifications()
    {
        $pending = Booking::with(['borrower', 'classroom', 'timeSlots'])
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'date' => $b->date,
                'created_at' => $b->created_at->diffForHumans(),
                'borrower_name' => $b->borrower?->name,
                'classroom_code' => $b->classroom?->code,
                'time_slots' => $b->timeSlots->pluck('name')->toArray(),
            ]);

        return response()->json([
            'count' => Booking::where('status', 0)->count(),
            'items' => $pending,
        ]);
    }

    /**
     * 顯示教室管理
     */
    public function rooms()
    {
        $classrooms = Classroom::orderBy('code')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'is_active' => (bool) $c->is_active,
                'bookings_count' => $c->bookings()->count(),
            ]);

        return Inertia::render('Admin/Rooms', [
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * 新增教室
     */
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:7|unique:classrooms,code',
            'name' => 'required|string|max:25',
        ]);

        Classroom::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return back()->with('success', '教室已新增。');
    }

    /**
     * 切換教室啟用狀態
     */
    public function toggleRoom(Classroom $classroom)
    {
        $classroom->update(['is_active' => !$classroom->is_active]);

        return back()->with('success', '教室狀態已更新。');
    }

    /**
     * 刪除教室
     */
    public function destroyRoom(Classroom $classroom)
    {
        if ($classroom->bookings()->exists()) {
            return back()->withErrors(['classroom' => '此教室有關聯的借用紀錄，無法刪除。']);
        }

        $classroom->delete();

        return back()->with('success', '教室已刪除。');
    }

    /**
     * 顯示用戶管理
     */
    public function users()
    {
        return Inertia::render('Admin/Users');
    }

    /**
     * 顯示系統設定
     */
    public function settings()
    {
        return Inertia::render('Admin/Settings');
    }
}
