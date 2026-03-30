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

        // Time slots for the manual form period picker (includes lunch break)
        $timeSlots = TimeSlot::query()
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

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        try {
            $importedSchedules = $this->fetchImportedSchedulesForClassrooms(
                $semester,
                $classrooms,
                $periodToSlotId
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => $e->getMessage(),
            ]);
        }

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

        $periodToSlotId = $this->buildPeriodToSlotIdMap();
        if (empty($periodToSlotId)) {
            return back()->withErrors(['import' => '時段資料不足，請先建立 time_slots。']);
        }

        try {
            $rows = $this->fetchImportedSchedulesForClassrooms(
                $semester,
                $classrooms,
                $periodToSlotId
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'import' => $e->getMessage(),
            ]);
        }

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
     * 手動長期借用使用的 1-based 節次對應時段 ID（包含午休）
     */
    private function buildManualPeriodToSlotIdMap(): array
    {
        $slots = TimeSlot::query()
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
     * 依教室大樓分批呼叫匯入服務，並合併結果
     *
     * @throws \RuntimeException
     */
    private function fetchImportedSchedulesForClassrooms(Semester $semester, Collection $classrooms, array $periodToSlotId): array
    {
        $groupedClassrooms = $classrooms
            ->groupBy(fn ($room) => $this->extractBuildingCode((string) $room->code) ?? '__UNKNOWN_BUILDING__');

        if ($groupedClassrooms->has('__UNKNOWN_BUILDING__')) {
            throw new \RuntimeException('教室代碼無法判斷大樓，僅支援 CB、GC、RA。');
        }

        $year = (int) $semester->academic_year;
        $seme = (int) $semester->semester;
        $category = (string) Setting::get('course_import_category', 'B');
        $importUrl = config('services.nfu_schedule_import.url');

        $allRows = [];

        foreach ($groupedClassrooms as $buildingCode => $roomsInBuilding) {
            $roomsInBuilding = $roomsInBuilding->values();

            $payload = [
                'year' => $year,
                'seme' => $seme,
                'category' => $category,
                'building' => $this->resolveImportBuildingValue((string) $buildingCode),
                'classrooms' => $roomsInBuilding
                    ->map(fn ($room) => "{$room->code},{$room->code}-{$room->name}")
                    ->values()
                    ->all(),
            ];

            try {
                $response = Http::timeout(45)
                    ->acceptJson()
                    ->post($importUrl, $payload);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('課表匯入服務連線失敗（%s 棟），請確認匯入服務是否啟動。', strtoupper((string) $buildingCode))
                );
            }

            if (!$response->successful()) {
                throw new \RuntimeException(
                    sprintf('課表匯入服務回應錯誤（%s 棟）：HTTP %d', strtoupper((string) $buildingCode), $response->status())
                );
            }

            $rows = $this->normalizeImportedSchedules(
                $response->json(),
                $semester->id,
                $roomsInBuilding,
                $periodToSlotId
            );

            $allRows = array_merge($allRows, $rows);
        }

        return $allRows;
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
                    'schedule' => 0,
                    'approved_short_term' => 0,
                    'pending_short_term' => 0,
                ],
            ], 422);
        }

        $periodToSlotId = $this->buildManualPeriodToSlotIdMap();
        $analysis = $this->analyzeManualConflicts($validated, $currentSemester, $periodToSlotId);

        return response()->json([
            'message' => '衝突分析完成。',
            'conflicts' => $analysis['conflicts'],
            'summary' => [
                'total' => count($analysis['conflicts']),
                'schedule' => $analysis['schedule_conflict_count'],
                'approved_short_term' => $analysis['approved_short_term_count'],
                'pending_short_term' => $analysis['pending_short_term_count'],
            ],
        ]);
    }

    /**
     * 立即執行手動長借衝突處理動作
     */
    public function resolveManualLongTermConflict(Request $request, BookingSlotLockService $bookingSlotLockService)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:cancel_slot,review_pending,reject_and_override,defer_to_short_term,override_with_long_term'],
            'booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $action = (string) $validated['action'];

        if ($action === 'review_pending') {
            return response()->json([
                'message' => '請前往審核清單處理未審核短期借用。',
                'redirect' => '/admin/reviews?from=long-term-borrowing',
            ]);
        }

        if ($action === 'cancel_slot' || $action === 'defer_to_short_term') {
            return response()->json([
                'message' => '衝突處理已套用。',
            ]);
        }

        $bookingId = (int) ($validated['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            return response()->json([
                'message' => '缺少短期借用識別資訊，請重新整理後再試。',
            ], 422);
        }

        $managerId = (int) (auth()->guard('admin')->id() ?? 0);

        DB::transaction(function () use ($bookingId, $managerId, $bookingSlotLockService) {
            $this->rejectBookingsByIds([$bookingId], $managerId, $bookingSlotLockService, Booking::activeStatusEnums());
        });

        return response()->json([
            'message' => '衝突處理已執行，該短期借用已整筆駁回。',
        ]);
    }

    /**
     * 手動新增長期借用記錄
     */
    public function storeManualLongTermBorrowing(Request $request, BookingSlotLockService $bookingSlotLockService)
    {
        $validated = $this->validateManualLongTermBorrowingPayload($request, true);

        $currentSemester = Semester::findByDate(now());
        if (!$currentSemester) {
            return back()->withErrors(['semester' => '目前沒有設定中的學期，請先建立學期資料。']);
        }

        $periodToSlotId = $this->buildManualPeriodToSlotIdMap();
        $analysis = $this->analyzeManualConflicts($validated, $currentSemester, $periodToSlotId);
        $conflictResolution = $validated['conflict_resolution'] ?? [];
        $slotResolutions = collect($validated['slot_resolutions'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
            ->filter(fn ($value) => $value !== '')
            ->all();
        $hasSlotResolutions = !empty($slotResolutions);

        $selectedByDay = $analysis['selected_by_day'];
        $pendingResolution = $conflictResolution['pending_short_term'] ?? null;
        $pendingRejectBookingIds = [];
        $approvedRejectBookingIds = [];

        if ($hasSlotResolutions) {
            $kindPriority = [
                'short_term_pending' => 1,
                'short_term_approved' => 2,
                'schedule' => 3,
            ];
            $conflictKindBySlot = [];
            $hasPendingReviewResolution = false;
            $hasUnresolvedPendingConflict = false;
            $hasUnresolvedApprovedConflict = false;

            foreach ($analysis['conflicts'] as $conflict) {
                $conflictKind = (string) ($conflict['conflict_kind'] ?? '');
                $periods = collect($conflict['overlap_periods'] ?? [])
                    ->map(fn ($period) => (int) $period)
                    ->filter(fn ($period) => $period > 0)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($periods)) {
                    continue;
                }

                $weekdays = [];
                if ($conflictKind === 'schedule') {
                    $weekday = (int) ($conflict['day_of_week'] ?? 0);
                    if ($weekday >= 1 && $weekday <= 7) {
                        $weekdays[] = $weekday;
                    }
                } else {
                    $weekdays = collect($conflict['conflict_dates'] ?? [])
                        ->map(function ($dateText) {
                            if (!is_string($dateText) || $dateText === '') {
                                return null;
                            }
                            $timestamp = strtotime($dateText);
                            if ($timestamp === false) {
                                return null;
                            }
                            return (int) date('N', $timestamp);
                        })
                        ->filter(fn ($weekday) => is_int($weekday) && $weekday >= 1 && $weekday <= 7)
                        ->unique()
                        ->values()
                        ->all();
                }

                foreach ($weekdays as $weekday) {
                    foreach ($periods as $period) {
                        $slotKey = $this->buildManualConflictSlotKey((int) $weekday, (int) $period);
                        $existingKind = $conflictKindBySlot[$slotKey] ?? null;
                        if (!$existingKind || ($kindPriority[$conflictKind] ?? 0) > ($kindPriority[$existingKind] ?? 0)) {
                            $conflictKindBySlot[$slotKey] = $conflictKind;
                        }
                    }
                }

                $conflictSlots = collect($conflict['conflict_slots'] ?? [])
                    ->filter(fn ($slot) => is_array($slot))
                    ->values();

                if (in_array($conflictKind, ['short_term_pending', 'short_term_approved'], true)) {
                    $bookingId = (int) ($conflict['booking_id'] ?? 0);

                    $bookingActions = $conflictSlots
                        ->map(function ($slot) use ($bookingId, $slotResolutions) {
                            $slotKey = (string) ($slot['slot_key'] ?? '');
                            if ($slotKey === '') {
                                $weekday = (int) ($slot['day_of_week'] ?? 0);
                                $period = (int) ($slot['period'] ?? 0);
                                if ($weekday >= 1 && $weekday <= 7 && $period > 0) {
                                    $slotKey = $this->buildManualConflictSlotKey($weekday, $period);
                                }
                            }

                            if ($slotKey === '') {
                                return null;
                            }

                            $bookingDateId = (int) ($slot['booking_date_id'] ?? 0);
                            $timeSlotId = (int) ($slot['time_slot_id'] ?? 0);

                            $resolutionKey = ($bookingDateId > 0 && $timeSlotId > 0)
                                ? $this->buildManualConflictResolutionKey($slotKey, $bookingDateId, $timeSlotId)
                                : null;
                            $legacyBookingKey = $bookingId > 0
                                ? $this->buildManualConflictLegacyResolutionKey($slotKey, $bookingId)
                                : null;

                            return $resolutionKey
                                ? ($slotResolutions[$resolutionKey] ?? $slotResolutions[$slotKey] ?? ($legacyBookingKey ? ($slotResolutions[$legacyBookingKey] ?? null) : null))
                                : ($slotResolutions[$slotKey] ?? ($legacyBookingKey ? ($slotResolutions[$legacyBookingKey] ?? null) : null));
                        })
                        ->filter(fn ($action) => is_string($action) && $action !== '')
                        ->values();

                    if ($conflictKind === 'short_term_pending') {
                        if ($bookingActions->contains('review_pending')) {
                            $hasPendingReviewResolution = true;
                            continue;
                        }

                        if ($bookingActions->contains('reject_and_override')) {
                            if ($bookingId > 0) {
                                $pendingRejectBookingIds[] = $bookingId;
                            }
                            continue;
                        }

                        $hasUnresolvedPendingConflict = true;
                        continue;
                    }

                    if ($bookingActions->contains('defer_to_short_term')) {
                        continue;
                    }

                    if ($bookingActions->contains('override_with_long_term')) {
                        if ($bookingId > 0) {
                            $approvedRejectBookingIds[] = $bookingId;
                        }
                        continue;
                    }

                    $hasUnresolvedApprovedConflict = true;
                    continue;
                }

                if ($conflictSlots->isNotEmpty()) {
                    foreach ($conflictSlots as $slot) {
                        $slotKey = (string) ($slot['slot_key'] ?? '');
                        if ($slotKey === '') {
                            $weekday = (int) ($slot['day_of_week'] ?? 0);
                            $period = (int) ($slot['period'] ?? 0);
                            if ($weekday >= 1 && $weekday <= 7 && $period > 0) {
                                $slotKey = $this->buildManualConflictSlotKey($weekday, $period);
                            }
                        }

                        if ($slotKey === '') {
                            continue;
                        }

                        // 保留對非短借衝突資料的向後相容掃描（目前主要是 schedule）。
                    }
                }
            }

            if ($hasPendingReviewResolution) {
                return back()->withErrors(['periods' => '偵測到未審核短期借用，請先前往審核清單處理後再新增。']);
            }

            if ($hasUnresolvedPendingConflict) {
                return back()->withErrors(['periods' => '請先處理未審核短期借用衝突。']);
            }

            if ($hasUnresolvedApprovedConflict) {
                return back()->withErrors(['periods' => '請先處理已審核短期借用衝突。']);
            }

            foreach ($selectedByDay as $weekday => $periods) {
                $keptPeriods = [];
                foreach ($periods as $period) {
                    $slotKey = $this->buildManualConflictSlotKey((int) $weekday, (int) $period);
                    $kind = $conflictKindBySlot[$slotKey] ?? null;
                    $action = $slotResolutions[$slotKey] ?? null;

                    if (!$kind) {
                        $keptPeriods[] = (int) $period;
                        continue;
                    }

                    if ($kind === 'schedule') {
                        if ($action !== 'cancel_slot') {
                            return back()->withErrors(['periods' => '存在課表衝突，請點擊衝突格選擇「取消該節」。']);
                        }
                        continue;
                    }

                    if ($kind === 'short_term_pending') {
                        $keptPeriods[] = (int) $period;
                        continue;
                    }

                    if ($kind === 'short_term_approved') {
                        // defer_to_short_term 僅處理當前衝突筆，不排除整個週期節次。
                        $keptPeriods[] = (int) $period;
                    }
                }

                $selectedByDay[$weekday] = collect($keptPeriods)
                    ->map(fn ($period) => (int) $period)
                    ->filter(fn ($period) => $period > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        } else {
            if (($analysis['schedule_conflict_count'] ?? 0) > 0) {
                return back()->withErrors(['periods' => '存在課表衝突，請先調整時段後再送出。']);
            }

            if (($analysis['approved_short_term_count'] ?? 0) > 0 && ($conflictResolution['approved_short_term'] ?? null) !== 'keep_short_term') {
                return back()->withErrors(['periods' => '請先確認「保留短期借用節數」後再送出。']);
            }

            $pendingConflictCount = (int) ($analysis['pending_short_term_count'] ?? 0);
            if ($pendingConflictCount > 0) {
                if ($pendingResolution === 'review_pending') {
                    return back()->withErrors(['periods' => '偵測到未審核短期借用，請先前往審核清單處理後再新增。']);
                }

                if ($pendingResolution !== 'reject_and_override') {
                    return back()->withErrors(['periods' => '請選擇未審核短期借用的處理方式。']);
                }
            }
        }

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

        $rejectedCount = 0;
        $managerId = auth()->guard('admin')->id();

        DB::transaction(function () use (
            $rows,
            $analysis,
            $pendingResolution,
            $pendingRejectBookingIds,
            $approvedRejectBookingIds,
            $hasSlotResolutions,
            $managerId,
            $bookingSlotLockService,
            &$rejectedCount
        ) {
            if ($hasSlotResolutions) {
                $rejectedCount += $this->rejectBookingsByIds(
                    array_merge($pendingRejectBookingIds, $approvedRejectBookingIds),
                    (int) $managerId,
                    $bookingSlotLockService,
                    Booking::activeStatusEnums()
                );
            } elseif ($pendingResolution === 'reject_and_override') {
                $pendingIds = collect($analysis['pending_conflict_booking_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values();

                $rejectedCount += $this->rejectBookingsByIds(
                    $pendingIds->all(),
                    (int) $managerId,
                    $bookingSlotLockService,
                    [Booking::STATUS_PENDING]
                );
            }

            foreach ($rows as $row) {
                $timeSlotIds = $row['time_slot_ids'] ?? [];
                unset($row['time_slot_ids']);

                $schedule = CourseSchedule::create($row);
                $schedule->timeSlots()->sync($timeSlotIds);
            }
        });

        $message = '長期借用記錄已新增，共 ' . count($rows) . ' 筆。';
        if ($rejectedCount > 0) {
            $message .= $hasSlotResolutions
                ? ' 已同步駁回 ' . $rejectedCount . ' 筆短期借用申請。'
                : ' 已覆蓋並拒絕 ' . $rejectedCount . ' 筆未審核短期借用。';
        }

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
            'conflict_resolution' => ['nullable', 'array'],
            'conflict_resolution.approved_short_term' => ['nullable', 'string', 'in:keep_short_term'],
            'conflict_resolution.pending_short_term' => ['nullable', 'string', 'in:review_pending,reject_and_override'],
            'slot_resolutions' => ['nullable', 'array'],
            'slot_resolutions.*' => ['nullable', 'string', 'in:cancel_slot,review_pending,reject_and_override,defer_to_short_term,override_with_long_term'],
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
                'schedule_conflict_count' => 0,
                'approved_short_term_count' => 0,
                'pending_short_term_count' => 0,
                'pending_conflict_booking_ids' => [],
                'approved_conflict_booking_ids' => [],
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
        $scheduleConflictCount = 0;

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
            $scheduleConflictCount++;

            $conflicts[] = [
                'id' => (int) $row->id,
                'conflict_kind' => 'schedule',
                'day_of_week' => $weekday,
                'start_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->first() ?? ''),
                'end_slot' => (string) ($row->timeSlots->sortBy('start_time')->pluck('name')->last() ?? ''),
                'start_date' => $row->start_date?->format('Y-m-d') ?? $semester->start_date?->format('Y-m-d'),
                'end_date' => $row->end_date?->format('Y-m-d') ?? $semester->end_date?->format('Y-m-d'),
                'type' => $type,
                'source_label' => $this->manualConflictSourceLabel($type),
                'course_name' => (string) ($row->course_name ?? ''),
                'teacher_name' => (string) ($row->teacher_name ?? ''),
                'is_protected' => true,
                'overlap_periods' => $overlapPeriods,
                'conflict_dates' => [],
                'booking_id' => null,
                'booking_status' => null,
                'applicant_name' => '',
            ];
        }

        $shortTermAnalysis = $this->analyzeShortTermBookingConflicts(
            (int) $validated['classroom_id'],
            $validated['start_date'],
            $validated['end_date'],
            $selectedSlotIdsByDay,
            $slotIdToPeriod
        );

        $conflicts = [...$conflicts, ...$shortTermAnalysis['conflicts']];

        return [
            'conflicts' => $conflicts,
            'schedule_conflict_count' => $scheduleConflictCount,
            'approved_short_term_count' => $shortTermAnalysis['approved_count'],
            'pending_short_term_count' => $shortTermAnalysis['pending_count'],
            'pending_conflict_booking_ids' => $shortTermAnalysis['pending_booking_ids'],
            'approved_conflict_booking_ids' => $shortTermAnalysis['approved_booking_ids'],
            'selected_by_day' => $selectedByDay,
        ];
    }

    private function analyzeShortTermBookingConflicts(
        int $classroomId,
        string $startDate,
        string $endDate,
        array $selectedSlotIdsByDay,
        array $slotIdToPeriod
    ): array {
        $bookings = Booking::with(['borrower', 'bookingDates.timeSlots'])
            ->where('classroom_id', $classroomId)
            ->whereIn('status_enum', Booking::activeStatusEnums())
            ->whereHas('bookingDates', function ($query) use ($startDate, $endDate) {
                $query->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate);
            })
            ->get();

        $aggregated = [];

        foreach ($bookings as $booking) {
            foreach ($booking->bookingDates as $bookingDate) {
                $dateText = $bookingDate->date?->format('Y-m-d');
                if (!$dateText || $dateText < $startDate || $dateText > $endDate) {
                    continue;
                }

                $weekday = (int) date('N', strtotime($dateText));
                $selectedSlotIds = $selectedSlotIdsByDay[$weekday] ?? [];
                if (empty($selectedSlotIds)) {
                    continue;
                }

                $bookingSlotIds = $bookingDate->timeSlots
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($bookingSlotIds)) {
                    continue;
                }

                $overlapSlotIds = array_values(array_intersect($selectedSlotIds, $bookingSlotIds));
                if (empty($overlapSlotIds)) {
                    continue;
                }

                $bookingId = (int) $booking->id;
                if (!isset($aggregated[$bookingId])) {
                    $aggregated[$bookingId] = [
                        'booking' => $booking,
                        'periods' => [],
                        'dates' => [],
                        'slots' => [],
                    ];
                }

                foreach ($overlapSlotIds as $slotId) {
                    $period = $slotIdToPeriod[(int) $slotId] ?? null;
                    if ($period) {
                        $aggregated[$bookingId]['periods'][] = (int) $period;
                        $aggregated[$bookingId]['slots'][] = [
                            'slot_key' => $this->buildManualConflictSlotKey($weekday, (int) $period),
                            'day_of_week' => $weekday,
                            'period' => (int) $period,
                            'date' => $dateText,
                            'booking_date_id' => (int) $bookingDate->id,
                            'time_slot_id' => (int) $slotId,
                        ];
                    }
                }

                $aggregated[$bookingId]['dates'][] = $dateText;
            }
        }

        $approvedCount = 0;
        $pendingCount = 0;
        $approvedBookingIds = [];
        $pendingBookingIds = [];
        $conflicts = [];

        foreach ($aggregated as $bookingId => $item) {
            /** @var Booking $booking */
            $booking = $item['booking'];
            $statusEnum = (string) $booking->status_enum;
            $isPending = $statusEnum === Booking::STATUS_PENDING;

            if ($isPending) {
                $pendingCount++;
                $pendingBookingIds[] = (int) $bookingId;
            } else {
                $approvedCount++;
                $approvedBookingIds[] = (int) $bookingId;
            }

            $overlapPeriods = collect($item['periods'])
                ->map(fn ($period) => (int) $period)
                ->filter(fn ($period) => $period > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $conflictDates = collect($item['dates'])
                ->filter(fn ($date) => is_string($date) && $date !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $conflictSlots = collect($item['slots'])
                ->filter(fn ($slot) => is_array($slot))
                ->unique(fn ($slot) => ($slot['booking_date_id'] ?? '0') . ':' . ($slot['time_slot_id'] ?? '0'))
                ->values()
                ->all();

            $conflicts[] = [
                'id' => 1000000000 + (int) $bookingId,
                'conflict_kind' => $isPending ? 'short_term_pending' : 'short_term_approved',
                'day_of_week' => 0,
                'start_slot' => '',
                'end_slot' => '',
                'start_date' => $conflictDates[0] ?? null,
                'end_date' => $conflictDates[count($conflictDates) - 1] ?? null,
                'type' => 'borrowed',
                'source_label' => $isPending ? '未審核短期借用' : '已審核短期借用',
                'course_name' => '',
                'teacher_name' => (string) ($booking->teacher ?? ''),
                'is_protected' => !$isPending,
                'overlap_periods' => $overlapPeriods,
                'conflict_dates' => $conflictDates,
                'booking_id' => (int) $bookingId,
                'booking_status' => $statusEnum,
                'applicant_name' => (string) ($booking->borrower?->name ?? ''),
                'conflict_slots' => $conflictSlots,
            ];
        }

        return [
            'conflicts' => $conflicts,
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'approved_booking_ids' => array_values(array_unique($approvedBookingIds)),
            'pending_booking_ids' => array_values(array_unique($pendingBookingIds)),
        ];
    }

    private function buildManualConflictSlotKey(int $dayOfWeek, int $period): string
    {
        return $dayOfWeek . ':' . $period;
    }

    private function buildManualConflictResolutionKey(string $slotKey, int $bookingDateId, int $timeSlotId): string
    {
        return $slotKey . '|bd:' . $bookingDateId . '|ts:' . $timeSlotId;
    }

    private function buildManualConflictLegacyResolutionKey(string $slotKey, int $bookingId): string
    {
        return $slotKey . '|booking:' . $bookingId;
    }

    private function rejectBookingsByIds(
        array $bookingIds,
        int $managerId,
        BookingSlotLockService $bookingSlotLockService,
        array $allowedStatuses
    ): int {
        $normalizedIds = collect($bookingIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return 0;
        }

        $bookings = Booking::with('bookingDates.timeSlots')
            ->whereIn('id', $normalizedIds->all())
            ->whereIn('status_enum', $allowedStatuses)
            ->lockForUpdate()
            ->get();

        foreach ($bookings as $booking) {
            $booking->status_enum = Booking::STATUS_REJECTED;
            $booking->level = Booking::levelForStatus(Booking::STATUS_REJECTED);
            $booking->rejected_by = $managerId > 0 ? $managerId : null;
            $booking->rejected_at = now();
            $booking->approved_by = null;
            $booking->approved_at = null;
            $booking->save();

            $bookingSlotLockService->syncForBooking($booking);
        }

        return $bookings->count();
    }

    private function applyBookingSlotOverrides(array $mutations, int $managerId, BookingSlotLockService $bookingSlotLockService): int
    {
        $normalized = collect($mutations)
            ->filter(fn ($mutation) => is_array($mutation))
            ->map(function ($mutation) {
                return [
                    'booking_id' => (int) ($mutation['booking_id'] ?? 0),
                    'booking_date_id' => (int) ($mutation['booking_date_id'] ?? 0),
                    'time_slot_id' => (int) ($mutation['time_slot_id'] ?? 0),
                ];
            })
            ->filter(fn ($mutation) => $mutation['booking_id'] > 0 && $mutation['booking_date_id'] > 0 && $mutation['time_slot_id'] > 0)
            ->unique(fn ($mutation) => $mutation['booking_date_id'] . ':' . $mutation['time_slot_id'])
            ->values();

        if ($normalized->isEmpty()) {
            return 0;
        }

        $bookingIds = $normalized
            ->pluck('booking_id')
            ->unique()
            ->values();

        $bookings = Booking::with('bookingDates.timeSlots')
            ->whereIn('id', $bookingIds->all())
            ->whereIn('status_enum', Booking::activeStatusEnums())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $mutationsByBooking = $normalized->groupBy('booking_id');
        $touchedBookingIds = [];

        foreach ($mutationsByBooking as $bookingId => $bookingMutations) {
            /** @var Booking|null $booking */
            $booking = $bookings->get((int) $bookingId);
            if (!$booking) {
                continue;
            }

            foreach ($bookingMutations as $mutation) {
                $bookingDate = BookingDate::query()
                    ->where('id', (int) $mutation['booking_date_id'])
                    ->where('booking_id', (int) $booking->id)
                    ->lockForUpdate()
                    ->first();

                if (!$bookingDate) {
                    continue;
                }

                $bookingDate->timeSlots()->detach((int) $mutation['time_slot_id']);

                $hasRemainingSlots = $bookingDate->timeSlots()->exists();
                if (!$hasRemainingSlots) {
                    $bookingDate->delete();
                }
            }

            $booking->load('bookingDates.timeSlots');

            if ($booking->bookingDates->isEmpty()) {
                $booking->status_enum = Booking::STATUS_REJECTED;
                $booking->level = Booking::levelForStatus(Booking::STATUS_REJECTED);
                $booking->rejected_by = $managerId > 0 ? $managerId : null;
                $booking->rejected_at = now();
                $booking->approved_by = null;
                $booking->approved_at = null;
                $booking->save();
            }

            $bookingSlotLockService->syncForBooking($booking);
            $touchedBookingIds[] = (int) $booking->id;
        }

        return count(array_unique($touchedBookingIds));
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
