<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use App\Models\Classroom;
use App\Models\Booking;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Models\Setting;
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


    // longTermBorrowing
    public function longTermBorrowing(Request $request)
    {
        $currentSemester = Semester::findByDate(now());

        $hasImportedIds = [];
        if ($currentSemester) {
            $hasImportedIds = CourseSchedule::where('semester_id', $currentSemester->id)
                ->whereNull('borrow_type')
                ->pluck('classroom_id')
                ->unique()
                ->toArray();
        }

        $classrooms = Classroom::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(function ($room) use ($hasImportedIds) {
                $room->has_imported = in_array($room->id, $hasImportedIds);
                $room->building_code = $this->extractBuildingCode((string) $room->code);
                return $room;
            });

        // Time slots (exclude lunch break) for the manual form period picker
        $timeSlots = TimeSlot::where('name', '!=', '午休')
            ->orderBy('start_time')
            ->get(['id', 'name']);

        // Existing manual long-term borrowing records (current semester)
        $manualRecords = [];
        if ($currentSemester) {
            $manualRecords = CourseSchedule::with(['classroom', 'startSlot', 'endSlot'])
                ->where('semester_id', $currentSemester->id)
                ->whereNotNull('borrow_type')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => [
                    'id'            => $r->id,
                    'classroom_code' => $r->classroom?->code ?? '—',
                    'classroom_name' => $r->classroom?->name ?? '—',
                    'borrow_type'   => $r->borrow_type,
                    'teacher_name'  => $r->teacher_name,
                    'course_name'   => $r->course_name,
                    'day_of_week'   => $r->day_of_week,
                    'start_slot'    => $r->startSlot?->name ?? '—',
                    'end_slot'      => $r->endSlot?->name ?? '—',
                    'start_date'    => $r->start_date?->format('Y-m-d'),
                    'end_date'      => $r->end_date?->format('Y-m-d'),
                ])
                ->toArray();
        }

        return Inertia::render('Admin/LongTermBorrowing', [
            'classrooms'   => $classrooms,
            'timeSlots'    => $timeSlots,
            'manualRecords' => $manualRecords,
            'importConfig' => [
                'year'     => (int) Setting::get('course_import_year', (string) ($currentSemester?->academic_year ?? 114)),
                'seme'     => (int) Setting::get('course_import_seme', (string) ($currentSemester?->semester ?? 2)),
                'category' => (string) Setting::get('course_import_category', 'B'),
                'building' => (string) Setting::get('course_import_building', 'GC,粽三館'),
            ],
        ]);
    }

    /**
     * 預覽選取教室的課表
     */
    public function previewCourseSchedules(Request $request)
    {
        $validated = $request->validate([
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        $classroomIds = collect($validated['classroom_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->get(['id', 'code', 'name']);

        if ($classrooms->isEmpty()) {
            return back()->withErrors(['classroom_ids' => '找不到可預覽的教室。']);
        }

        $year = (int) Setting::get('course_import_year', '114');
        $seme = (int) Setting::get('course_import_seme', '2');
        $category = (string) Setting::get('course_import_category', 'B');
        $selectedBuildingCode = $this->validateSingleBuildingSelection($classrooms);
        if (!$selectedBuildingCode) {
            return back()->withErrors([
                'classroom_ids' => '只能批量匯入同一大樓教室（CB、GC、RA）。',
            ]);
        }

        $building = $this->resolveImportBuildingValue($selectedBuildingCode);

        $semester = Semester::query()
            ->where('academic_year', $year)
            ->where('semester', $seme)
            ->first();

        if (!$semester) {
            return back()->withErrors([
                'import' => "找不到 {$year} 學年 {$seme} 學期，請先建立學期資料。",
            ]);
        }

        $payload = [
            'year' => $year,
            'seme' => $seme,
            'category' => $category,
            'building' => $building,
            'classrooms' => $classrooms
                ->map(fn ($room) => "{$room->code},{$room->code}-{$room->name}")
                ->values()
                ->all(),
        ];

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        $importUrl = config('services.nfu_schedule_import.url');

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->post($importUrl, $payload);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => '課表匯入服務連線失敗，請確認匯入服務是否啟動。',
            ]);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'import' => '課表匯入服務回應錯誤：HTTP ' . $response->status(),
            ]);
        }

        $data = $response->json();
        $importedSchedules = $this->normalizeImportedSchedules($data, $semester->id, $classrooms, $periodToSlotId);

        return response()->json([
            'schedules' => collect($importedSchedules)->map(function ($item) {
                // Ensure correct shape for the frontend preview
                return [
                     'classroom_id' => $item['classroom_id'],
                     'start_slot_id' => $item['start_slot_id'],
                     'end_slot_id' => $item['end_slot_id'],
                     'day_of_week' => $item['day_of_week'],
                     'course_name' => collect([$item['course_name'], $item['teacher_name']])->filter()->join(' - '),
                     'start_date' => null,
                     'end_date' => null,
                ];
            })->values(),
        ]);
    }

    /**
     * 依照選取教室匯入課表
     */
    public function importCourseSchedules(Request $request)
    {
        $validated = $request->validate([
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        $classroomIds = collect($validated['classroom_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->get(['id', 'code', 'name']);

        if ($classrooms->isEmpty()) {
            return back()->withErrors(['classroom_ids' => '找不到可匯入的教室。']);
        }

        $year = (int) Setting::get('course_import_year', '114');
        $seme = (int) Setting::get('course_import_seme', '2');
        $category = (string) Setting::get('course_import_category', 'B');
        $selectedBuildingCode = $this->validateSingleBuildingSelection($classrooms);
        if (!$selectedBuildingCode) {
            return back()->withErrors([
                'classroom_ids' => '只能批量匯入同一大樓教室（CB、GC、RA）。',
            ]);
        }

        $building = $this->resolveImportBuildingValue($selectedBuildingCode);

        $semester = Semester::query()
            ->where('academic_year', $year)
            ->where('semester', $seme)
            ->first();

        if (!$semester) {
            return back()->withErrors([
                'import' => "找不到 {$year} 學年 {$seme} 學期，請先建立學期資料。",
            ]);
        }

        $payload = [
            'year' => $year,
            'seme' => $seme,
            'category' => $category,
            'building' => $building,
            'classrooms' => $classrooms
                ->map(fn ($room) => "{$room->code},{$room->code}-{$room->name}")
                ->values()
                ->all(),
        ];

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        $importUrl = config('services.nfu_schedule_import.url');

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->post($importUrl, $payload);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => '課表匯入服務連線失敗，請確認匯入服務是否啟動。',
            ]);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'import' => '課表匯入服務回應錯誤：HTTP ' . $response->status(),
            ]);
        }

        $rows = $this->normalizeImportedSchedules(
            $response->json(),
            $semester->id,
            $classrooms,
            $periodToSlotId
        );

        DB::transaction(function () use ($semester, $classroomIds, $rows) {
            CourseSchedule::where('semester_id', $semester->id)
                ->whereIn('classroom_id', $classroomIds)
                ->delete();

            if (!empty($rows)) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    CourseSchedule::insert($chunk);
                }
            }
        });

        return back()->with('success', '課表匯入完成，共新增 ' . count($rows) . ' 筆課程。');
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

    /**
     * 依照時段順序建立 1-based 節次對應時段 ID（排除午休）
     */
    private function buildPeriodToSlotIdMap(): array
    {
        $slots = TimeSlot::query()
            ->where('name', '!=', '午休')
            ->orderBy('start_time')
            ->get(['id']);

        $map = [];
        foreach ($slots->values() as $index => $slot) {
            $map[$index + 1] = $slot->id;
        }

        return $map;
    }

    /**
     * 將匯入服務回傳資料轉為 course_schedules 欄位格式
     */
    private function normalizeImportedSchedules($raw, int $semesterId, $classrooms, array $periodToSlotId): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $now = now();
        $result = [];
        $dedup = [];

        foreach ($raw as $classroomSchedule) {
            if (!is_array($classroomSchedule)) {
                continue;
            }

            $cid = strtoupper(trim((string) ($classroomSchedule['cid'] ?? '')));
            if ($cid === '') {
                continue;
            }

            $classroom = $classrooms->first(fn ($room) => strtoupper((string) $room->code) === $cid);
            if (!$classroom) {
                continue;
            }

            $records = $classroomSchedule['r'] ?? [];
            if (!is_array($records)) {
                continue;
            }

            foreach ($records as $record) {
                if (!is_array($record)) {
                    continue;
                }

                $dayOfWeek = (int) ($record['d'] ?? 0);
                if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                    continue;
                }

                $periods = collect($record['p'] ?? [])
                    ->map(fn ($n) => (int) $n)
                    ->filter(fn ($n) => $n > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                if (empty($periods)) {
                    continue;
                }

                $startPeriod = min($periods);
                $endPeriod = max($periods);
                $startSlotId = $periodToSlotId[$startPeriod] ?? null;
                $endSlotId = $periodToSlotId[$endPeriod] ?? null;

                if (!$startSlotId || !$endSlotId) {
                    continue;
                }

                $courseName = Str::limit(trim((string) ($record['n'] ?? '未命名課程')), 100, '');
                $teacherName = trim((string) ($record['i'] ?? ''));
                $teacherName = $teacherName === '' ? null : Str::limit($teacherName, 50, '');

                $hash = implode('|', [
                    $semesterId,
                    $classroom->id,
                    $courseName,
                    $teacherName,
                    $dayOfWeek,
                    $startSlotId,
                    $endSlotId,
                ]);

                if (isset($dedup[$hash])) {
                    continue;
                }
                $dedup[$hash] = true;

                $result[] = [
                    'semester_id' => $semesterId,
                    'classroom_id' => $classroom->id,
                    'course_name' => $courseName,
                    'teacher_name' => $teacherName,
                    'day_of_week' => $dayOfWeek,
                    'start_slot_id' => $startSlotId,
                    'end_slot_id' => $endSlotId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $result;
    }

    /**
     * 從教室代碼擷取大樓代碼（CB/GC/RA）
     */
    private function extractBuildingCode(string $roomCode): ?string
    {
        $upper = strtoupper($roomCode);
        foreach (['CB', 'GC', 'RA'] as $buildingCode) {
            if (str_contains($upper, $buildingCode)) {
                return $buildingCode;
            }
        }

        return null;
    }

    /**
     * 檢查是否只選到單一大樓
     */
    private function validateSingleBuildingSelection(Collection $classrooms): ?string
    {
        $codes = $classrooms
            ->map(fn ($room) => $this->extractBuildingCode((string) $room->code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->count() !== 1) {
            return null;
        }

        return $codes->first();
    }

    /**
     * 轉換為匯入服務所需的 building 參數
     */
    private function resolveImportBuildingValue(string $buildingCode): string
    {
        $default = (string) Setting::get('course_import_building', 'GC,粽三館');

        return match (strtoupper($buildingCode)) {
            'CB' => 'CB,跨領域',
            'GC' => 'GC,粽三館',
            'RA' => 'RA,科研大樓',
            default => $default,
        };
    }

    /**
     * 手動新增長期借用記錄
     */
    public function storeManualLongTermBorrowing(Request $request)
    {
        $validated = $request->validate([
            'borrow_type'   => ['required', 'integer', 'in:1,2'],
            'classroom_id'  => ['required', 'integer', 'exists:classrooms,id'],
            'teacher_name'  => ['required', 'string', 'max:50'],
            'course_name'   => ['nullable', 'string', 'max:100'],
            'day_of_week'   => ['required', 'array', 'min:1'],
            'day_of_week.*' => ['integer', 'between:1,7'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'periods'       => ['required', 'array', 'min:1'],
            'periods.*'     => ['integer', 'min:1'],
        ]);

        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return back()->withErrors(['semester' => '目前沒有設定中的學期，請先建立學期資料。']);
        }

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        $periods = collect($validated['periods'])->unique()->sort()->values();

        $startSlotId = $periodToSlotId[$periods->min()] ?? null;
        $endSlotId   = $periodToSlotId[$periods->max()] ?? null;

        if (!$startSlotId || !$endSlotId) {
            return back()->withErrors(['periods' => '節次對應的時段不存在，請確認時段設定。']);
        }

        $daysOfWeek = collect($validated['day_of_week'])->unique()->sort()->values();
        $rows = [];
        $now  = now();

        foreach ($daysOfWeek as $day) {
            $rows[] = [
                'semester_id'  => $currentSemester->id,
                'classroom_id' => (int) $validated['classroom_id'],
                'borrow_type'  => (int) $validated['borrow_type'],
                'teacher_name' => $validated['teacher_name'],
                'course_name'  => $validated['course_name'] ?? '',
                'day_of_week'  => (int) $day,
                'start_slot_id' => $startSlotId,
                'end_slot_id'   => $endSlotId,
                'start_date'   => $validated['start_date'],
                'end_date'     => $validated['end_date'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        CourseSchedule::insert($rows);

        return back()->with('success', '長期借用記錄已新增，共 ' . count($rows) . ' 筆。');
    }

    /**
     * 撤回已匯入教室的課表
     */
    public function revokeClassroomImport(Classroom $classroom)
    {
        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return back()->withErrors(['revoke' => '目前沒有設定中的學期。']);
        }

        $deleted = CourseSchedule::where('semester_id', $currentSemester->id)
            ->where('classroom_id', $classroom->id)
            ->whereNull('borrow_type')
            ->delete();

        return back()->with('success', "已撤回 {$classroom->code} 的課表匯入，共刪除 {$deleted} 筆。");
    }
}
