<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use App\Models\Classroom;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Blacklist;
use App\Models\Borrower;
use App\Models\CourseSchedule;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\BlacklistDetail;
use App\Models\BlacklistReason;
use App\Models\TimeSlot;
use App\Services\BookingSlotLockService;

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

        // --- 圖表 4: 各時段借用熱度 (本學期) ---
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

    /**
     * 顯示預約管理
     */
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

        // 篩選狀態
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusEnum = $this->resolveStatusEnumFromFilter($request->input('status'));
            if ($statusEnum) {
                $query->where('status_enum', $statusEnum);
            }
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

    /**
     * 審核列表（僅待審核）
     */
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

    /**
     * 短期借用紀錄（已審核）
     */
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


    // longTermBorrowing
    public function longTermBorrowing(Request $request)
    {
        $currentSemester = Semester::findByDate(now());
        $importSemester = $this->resolveCurrentOrNearestFutureSemester();

        $hasImportedIds = [];
        if ($importSemester) {
            $hasImportedIds = CourseSchedule::where('semester_id', $importSemester->id)
                ->where('type', 'course')
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
            $manualRecords = CourseSchedule::with(['classroom', 'timeSlots'])
                ->where('semester_id', $currentSemester->id)
                ->whereIn('type', ['manual', 'borrowed'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($r) {
                    $slotNames = $r->timeSlots
                        ->sortBy('start_time')
                        ->pluck('name')
                        ->values();

                    return [
                        'id'            => $r->id,
                        'classroom_code' => $r->classroom?->code ?? '—',
                        'classroom_name' => $r->classroom?->name ?? '—',
                        'type'          => $r->type,
                        'teacher_name'  => $r->teacher_name,
                        'course_name'   => $r->course_name,
                        'day_of_week'   => $r->day_of_week,
                        'start_slot'    => $slotNames->first() ?? '—',
                        'end_slot'      => $slotNames->last() ?? '—',
                        'start_date'    => $r->start_date?->format('Y-m-d'),
                        'end_date'      => $r->end_date?->format('Y-m-d'),
                    ];
                })
                ->toArray();
        }

        return Inertia::render('Admin/LongTermBorrowing', [
            'classrooms'   => $classrooms,
            'timeSlots'    => $timeSlots,
            'manualRecords' => $manualRecords,
            'semesterEndDate' => $currentSemester?->end_date?->format('Y-m-d'),
            'importConfig' => [
                'year'     => (int) ($importSemester?->academic_year ?? Setting::get('course_import_year', (string) ($currentSemester?->academic_year ?? 114))),
                'seme'     => (int) ($importSemester?->semester ?? Setting::get('course_import_seme', (string) ($currentSemester?->semester ?? 2))),
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

        $semester = $this->resolveCurrentOrNearestFutureSemester();
        if (!$semester) {
            return back()->withErrors([
                'import' => '找不到目前或未來學期，請先建立學期資料。',
            ]);
        }

        $year = (int) $semester->academic_year;
        $seme = (int) $semester->semester;
        $category = (string) Setting::get('course_import_category', 'B');
        $selectedBuildingCode = $this->validateSingleBuildingSelection($classrooms);
        if (!$selectedBuildingCode) {
            return back()->withErrors([
                'classroom_ids' => '只能批量匯入同一大樓教室（CB、GC、RA）。',
            ]);
        }

        $building = $this->resolveImportBuildingValue($selectedBuildingCode);

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
        $semesterStartDate = $semester->start_date?->format('Y-m-d');
        $semesterEndDate = $semester->end_date?->format('Y-m-d');

        return response()->json([
            'schedules' => collect($importedSchedules)->map(function ($item) {
                // Ensure correct shape for the frontend preview
                return [
                     'classroom_id' => $item['classroom_id'],
                     'time_slot_ids' => $item['time_slot_ids'],
                     'day_of_week' => $item['day_of_week'],
                     'course_name' => collect([$item['course_name'], $item['teacher_name']])->filter()->join(' - '),
                     'start_date' => $item['start_date'] ?? null,
                     'end_date' => $item['end_date'] ?? null,
                ];
            })->values(),
            'semester_range' => [
                'start_date' => $semesterStartDate,
                'end_date' => $semesterEndDate,
            ],
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

        $semester = $this->resolveCurrentOrNearestFutureSemester();
        if (!$semester) {
            return back()->withErrors([
                'import' => '找不到目前或未來學期，請先建立學期資料。',
            ]);
        }

        $year = (int) $semester->academic_year;
        $seme = (int) $semester->semester;
        $category = (string) Setting::get('course_import_category', 'B');
        $selectedBuildingCode = $this->validateSingleBuildingSelection($classrooms);
        if (!$selectedBuildingCode) {
            return back()->withErrors([
                'classroom_ids' => '只能批量匯入同一大樓教室（CB、GC、RA）。',
            ]);
        }

        $building = $this->resolveImportBuildingValue($selectedBuildingCode);

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
                foreach ($rows as $row) {
                    $timeSlotIds = $row['time_slot_ids'] ?? [];
                    unset($row['time_slot_ids']);

                    $schedule = CourseSchedule::create($row);
                    $schedule->timeSlots()->sync($timeSlotIds);
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
        $summary = $booking->getDateSummaryData('Y-m-d');
        $bookingDateItems = $booking->bookingDates
            ->map(function ($bookingDate) {
                return [
                    'date' => $bookingDate->date?->format('Y-m-d') ?? null,
                    'time_slots' => $bookingDate->timeSlots->pluck('name')->values()->all(),
                ];
            })
            ->filter(fn ($item) => !empty($item['date']))
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

    /**
     * 更新預約狀態
     */
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

    /**
     * 取得待審核預約通知 (JSON)
     */
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
    public function users(Request $request)
    {
        $query = Blacklist::with([
            'borrower:id,identity_code,name,department',
            'blacklistDetails.reason:id,reason',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('borrower', function ($q) use ($search) {
                $q->where('identity_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $blacklists = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($blacklist) {
                return [
                    'id' => $blacklist->id,
                    'borrower_identity_code' => $blacklist->borrower?->identity_code,
                    'borrower_name' => $blacklist->borrower?->name,
                    'borrower_department' => $blacklist->borrower?->department,
                    'banned_until' => $blacklist->banned_until?->format('Y-m-d'),
                    'reasons' => $blacklist->blacklistDetails
                        ->map(fn ($detail) => $detail->reason?->reason)
                        ->filter()
                        ->values()
                        ->all(),
                ];
            });

        return Inertia::render('Admin/Blacklist', [
            'blacklists' => $blacklists,
            'blacklistReasons' => BlacklistReason::query()
                ->orderBy('id')
                ->get(['id', 'reason']),
            'defaultBannedUntil' => $this->resolveDefaultBlacklistEndDate(),
            'storeBlacklistUrl' => route('admin.users.blacklist.store'),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * 新增黑名單紀錄（保留歷史）
     */
    public function storeBlacklist(Request $request)
    {
        $validated = $request->validate([
            'identity_code' => ['required', 'string', 'exists:borrowers,identity_code'],
            'reason_ids' => ['required', 'array', 'min:1'],
            'reason_ids.*' => ['integer', 'distinct', 'exists:blacklist_reasons,id'],
            'banned_until' => ['nullable', 'date'],
        ]);

        $borrower = Borrower::query()
            ->where('identity_code', $validated['identity_code'])
            ->firstOrFail();

        $bannedUntilDate = (string) ($validated['banned_until'] ?? $this->resolveDefaultBlacklistEndDate());
        $reasonIds = collect($validated['reason_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($borrower, $bannedUntilDate, $reasonIds) {
            $blacklist = Blacklist::create([
                'borrower_id' => $borrower->id,
                'banned_until' => $bannedUntilDate . ' 23:59:59',
            ]);

            $blacklist->blacklistDetails()->createMany(
                $reasonIds->map(fn ($reasonId) => ['reason_id' => $reasonId])->all()
            );
        });

        return back()->with('success', '黑名單已新增。');
    }

    /**
     * 顯示系統設定
     */
    public function settings()
    {
        return Inertia::render('Admin/Settings', [
            'currentSemester' => Semester::findByDate(now())?->display_name,
            'semesters' => Semester::query()
                ->orderByDesc('start_date')
                ->get()
                ->map(fn ($semester) => [
                    'id' => $semester->id,
                    'academic_year' => $semester->academic_year,
                    'semester' => $semester->semester,
                    'display_name' => $semester->display_name,
                    'start_date' => $semester->start_date?->format('Y-m-d'),
                    'end_date' => $semester->end_date?->format('Y-m-d'),
                ])
                ->values(),
        ]);
    }

    /**
     * 新增學期資料
     */
    public function storeSemester(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', 'min:1', 'max:999'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $existsSameTerm = Semester::query()
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($existsSameTerm) {
            return back()->withErrors([
                'semester' => '相同學年與學期已存在。',
            ]);
        }

        $overlapping = Semester::overlapping($validated['start_date'], $validated['end_date']);
        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_date' => '日期區間與既有學期重疊，請調整起訖日期。',
            ]);
        }

        Semester::create($validated);

        return back()->with('success', '學期資料已新增。');
    }

    /**
     * 預設停權結束日：目前學期結束日，找不到則使用今天
     */
    private function resolveDefaultBlacklistEndDate(): string
    {
        $semester = Semester::findByDate(now())
            ?? Semester::query()
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderBy('end_date')
                ->first();

        return $semester?->end_date?->format('Y-m-d') ?? now()->toDateString();
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

        $semester = Semester::query()->find($semesterId);
        if (!$semester || !$semester->start_date || !$semester->end_date) {
            return [];
        }

        $semesterStartDate = $semester->start_date->format('Y-m-d');
        $semesterEndDate = $semester->end_date->format('Y-m-d');

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

                $timeSlotIds = collect($periods)
                    ->map(fn ($period) => $periodToSlotId[(int) $period] ?? null)
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($timeSlotIds)) {
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
                    implode(',', $timeSlotIds),
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
                    'type' => 'course',
                    'time_slot_ids' => $timeSlotIds,
                    'start_date' => $semesterStartDate,
                    'end_date' => $semesterEndDate,
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
    public function previewManualLongTermBorrowingConflicts(Request $request)
    {
        $validated = $this->validateManualLongTermBorrowingPayload($request, false);

        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return response()->json([
                'message' => '目前沒有設定中的學期，請先建立學期資料。',
                'conflicts' => [],
                'summary' => [
                    'total' => 0,
                    'protected' => 0,
                    'overridable' => 0,
                ],
            ], 422);
        }

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        $analysis = $this->analyzeManualConflicts($validated, $currentSemester, $periodToSlotId);

        return response()->json([
            'message' => '衝突分析完成。',
            'conflicts' => $analysis['conflicts'],
            'summary' => [
                'total' => count($analysis['conflicts']),
                'protected' => $analysis['protected_count'],
                'overridable' => $analysis['overridable_count'],
            ],
        ]);
    }

    /**
     * 手動新增長期借用記錄
     */
    public function storeManualLongTermBorrowing(Request $request)
    {
        $validated = $this->validateManualLongTermBorrowingPayload($request, true);

        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return back()->withErrors(['semester' => '目前沒有設定中的學期，請先建立學期資料。']);
        }

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        $analysis = $this->analyzeManualConflicts($validated, $currentSemester, $periodToSlotId);

        if (count($analysis['conflicts']) > 0) {
            return back()->withErrors(['periods' => '存在衝突記錄，請先調整條件至無衝突後再送出。']);
        }

        $selectedByDay = $analysis['selected_by_day'];

        $slotGroupsByDay = [];
        foreach ($selectedByDay as $weekday => $periodIndexes) {
            $slotGroupsByDay[$weekday] = $this->buildSlotGroupsFromPeriods($periodIndexes, $periodToSlotId);
        }

        $rows = [];
        $now  = now();

        foreach ($slotGroupsByDay as $weekday => $slotGroups) {
            foreach ($slotGroups as $slotIds) {
                $rows[] = [
                    'semester_id'  => $currentSemester->id,
                    'classroom_id' => (int) $validated['classroom_id'],
                    'type'        => 'manual',
                    'teacher_name' => $validated['teacher_name'],
                    'course_name'  => $validated['course_name'] ?? '',
                    'day_of_week'  => (int) $weekday,
                    'time_slot_ids' => $slotIds,
                    'start_date'   => $validated['start_date'],
                    'end_date'     => $validated['end_date'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        if (empty($rows)) {
            return back()->withErrors(['periods' => '所選節次皆與既有課表衝突，沒有可新增的時段。']);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $timeSlotIds = $row['time_slot_ids'] ?? [];
                unset($row['time_slot_ids']);

                $schedule = CourseSchedule::create($row);
                $schedule->timeSlots()->sync($timeSlotIds);
            }
        });

        $message = '長期借用記錄已新增，共 ' . count($rows) . ' 筆。';

        return back()->with('success', $message);
    }

    private function validateManualLongTermBorrowingPayload(Request $request, bool $forStore): array
    {
        $rules = [
            'classroom_id'  => ['required', 'integer', 'exists:classrooms,id'],
            'teacher_name'  => $forStore
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
            'course_name'   => ['nullable', 'string', 'max:100'],
            'day_of_week'   => ['required', 'array', 'min:1'],
            'day_of_week.*' => ['integer', 'between:1,7'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'periods'       => ['required', 'array', 'min:1'],
            'periods.*'     => ['integer', 'min:1'],
            'periods_by_day' => ['nullable', 'array'],
            'periods_by_day.*' => ['array', 'min:1'],
            'periods_by_day.*.*' => ['integer', 'min:1'],
        ];

        return $request->validate($rules);
    }

    private function analyzeManualConflicts(array $validated, Semester $semester, array $periodToSlotId): array
    {
        $dayOfWeeks = collect($validated['day_of_week'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
        $selectedByDay = $this->buildSelectedPeriodsByDay($validated, $dayOfWeeks);
        $selectedSlotIdsByDay = [];
        foreach ($selectedByDay as $weekday => $periods) {
            $selectedSlotIdsByDay[(int) $weekday] = collect($periods)
                ->map(fn ($period) => $periodToSlotId[(int) $period] ?? null)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $hasSelectableSlot = collect($selectedSlotIdsByDay)
            ->contains(fn ($slotIds) => !empty($slotIds));

        if (!$hasSelectableSlot) {
            return [
                'conflicts' => [],
                'protected_count' => 0,
                'overridable_count' => 0,
                'selected_by_day' => $selectedByDay,
            ];
        }

        $slotIdToPeriod = [];
        foreach ($periodToSlotId as $period => $slotId) {
            $slotIdToPeriod[(int) $slotId] = (int) $period;
        }

        $semesterStart = $semester->start_date?->format('Y-m-d');
        $semesterEnd = $semester->end_date?->format('Y-m-d');
        $semesterOverlapsRequest = $semesterStart
            && $semesterEnd
            && $semesterStart <= $validated['end_date']
            && $semesterEnd >= $validated['start_date'];

        $rows = CourseSchedule::with(['semester', 'timeSlots'])
            ->where('semester_id', (int) $semester->id)
            ->where('classroom_id', (int) $validated['classroom_id'])
            ->whereIn('day_of_week', $dayOfWeeks)
            ->where(function ($query) use ($validated, $semesterOverlapsRequest) {
                $query
                    ->where(function ($manual) use ($validated) {
                        $manual
                            ->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('start_date', '<=', $validated['end_date'])
                            ->whereDate('end_date', '>=', $validated['start_date']);
                    });

                if ($semesterOverlapsRequest) {
                    $query->orWhere(function ($imported) {
                        $imported->where('type', 'course');
                    });
                }
            })
            ->get();

        $conflicts = [];
        $protectedCount = 0;
        $overridableCount = 0;

        foreach ($rows as $row) {
            $weekday = (int) $row->day_of_week;
            $selectedSlotIds = $selectedSlotIdsByDay[$weekday] ?? [];
            if (empty($selectedSlotIds)) {
                continue;
            }

            $existingSlotIds = $row->timeSlots
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            if (empty($existingSlotIds)) {
                continue;
            }

            $overlapSlotIds = array_values(array_intersect($selectedSlotIds, $existingSlotIds));
            if (empty($overlapSlotIds)) {
                continue;
            }

            $overlapPeriods = collect($overlapSlotIds)
                ->map(fn ($slotId) => $slotIdToPeriod[(int) $slotId] ?? null)
                ->filter()
                ->map(fn ($period) => (int) $period)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (empty($overlapPeriods)) {
                continue;
            }

            $type = $this->resolveScheduleType($row->type);
            $isProtected = $type === 'course';
            if ($isProtected) {
                $protectedCount++;
            } else {
                $overridableCount++;
            }

            $conflicts[] = [
                'id' => (int) $row->id,
                'day_of_week' => $weekday,
                'start_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->first() ?? ''),
                'end_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->last() ?? ''),
                'start_date' => $row->start_date?->format('Y-m-d') ?? $semester->start_date?->format('Y-m-d'),
                'end_date' => $row->end_date?->format('Y-m-d') ?? $semester->end_date?->format('Y-m-d'),
                'type' => $type,
                'source_label' => $this->manualConflictSourceLabel($type),
                'course_name' => (string) ($row->course_name ?? ''),
                'teacher_name' => (string) ($row->teacher_name ?? ''),
                'is_protected' => $isProtected,
                'overlap_periods' => $overlapPeriods,
            ];
        }

        return [
            'conflicts' => $conflicts,
            'protected_count' => $protectedCount,
            'overridable_count' => $overridableCount,
            'selected_by_day' => $selectedByDay,
        ];
    }

    private function buildSelectedPeriodsByDay(array $validated, array $dayOfWeeks): array
    {
        $selectedByDay = [];

        $rawByDay = $validated['periods_by_day'] ?? null;
        if (is_array($rawByDay)) {
            foreach ($dayOfWeeks as $weekday) {
                $selectedByDay[(int) $weekday] = collect($rawByDay[(string) $weekday] ?? $rawByDay[(int) $weekday] ?? [])
                    ->map(fn ($p) => (int) $p)
                    ->filter(fn ($p) => $p > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }

            return $selectedByDay;
        }

        // Backward compatibility: old payload only has a global periods array.
        $selectedPeriods = collect($validated['periods'] ?? [])
            ->map(fn ($p) => (int) $p)
            ->filter(fn ($p) => $p > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach ($dayOfWeeks as $weekday) {
            $selectedByDay[(int) $weekday] = $selectedPeriods;
        }

        return $selectedByDay;
    }

    private function manualConflictSourceLabel(string $type): string
    {
        return match ($type) {
            'course' => '課表匯入',
            'borrowed' => '一般借用',
            default => '手動課程',
        };
    }

    private function resolveScheduleType(?string $type): string
    {
        return in_array($type, ['course', 'manual', 'borrowed'], true) ? $type : 'manual';
    }

    private function resolveCurrentOrNearestFutureSemester(): ?Semester
    {
        $currentSemester = Semester::findByDate(now());
        if ($currentSemester) {
            return $currentSemester;
        }

        return Semester::query()
            ->whereDate('start_date', '>', now()->toDateString())
            ->orderBy('start_date')
            ->first();
    }

    private function buildSlotGroupsFromPeriods(array $periods, array $periodToSlotId): array
    {
        $indexes = collect($periods)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($indexes)) {
            return [];
        }

        $groups = [];
        $chunkStart = $indexes[0];
        $prev = $indexes[0];

        for ($i = 1; $i < count($indexes); $i++) {
            $current = $indexes[$i];
            if ($current !== $prev + 1) {
                $slotIds = [];
                for ($p = $chunkStart; $p <= $prev; $p++) {
                    if (isset($periodToSlotId[$p])) {
                        $slotIds[] = (int) $periodToSlotId[$p];
                    }
                }

                if (!empty($slotIds)) {
                    $groups[] = $slotIds;
                }
                $chunkStart = $current;
            }
            $prev = $current;
        }

        $slotIds = [];
        for ($p = $chunkStart; $p <= $prev; $p++) {
            if (isset($periodToSlotId[$p])) {
                $slotIds[] = (int) $periodToSlotId[$p];
            }
        }

        if (!empty($slotIds)) {
            $groups[] = $slotIds;
        }

        return $groups;
    }

    /**
     * 撤回已匯入教室的課表
     */
    public function revokeClassroomImport(Classroom $classroom)
    {
        $targetSemester = $this->resolveCurrentOrNearestFutureSemester();
        if (!$targetSemester) {
            return back()->withErrors(['revoke' => '目前沒有設定中的學期。']);
        }

        $deleted = CourseSchedule::where('semester_id', $targetSemester->id)
            ->where('classroom_id', $classroom->id)
            ->where('type', 'course')
            ->delete();

        return back()->with('success', "已撤回 {$classroom->code} 的課表匯入，共刪除 {$deleted} 筆。");
    }

    public function revokeManualLongTermBorrowing(CourseSchedule $schedule)
    {
        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return back()->withErrors(['revoke' => '目前沒有設定中的學期。']);
        }

        if ((int) $schedule->semester_id !== (int) $currentSemester->id || !in_array($this->resolveScheduleType($schedule->type), ['manual', 'borrowed'], true)) {
            return back()->withErrors(['revoke' => '僅能撤回本學期手動新增的長期借用記錄。']);
        }

        $schedule->delete();

        return back()->with('success', '已撤回一筆手動長期借用記錄。');
    }
}
