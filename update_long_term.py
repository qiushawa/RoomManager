from pathlib import Path

def edit(name, fn):
    p=Path(name); p.write_text(fn(p.read_text(encoding='utf-8')), encoding='utf-8')

edit('app/Models/Semester.php', lambda s:s.replace("where('start_date', '<=', $date)", "whereDate('start_date', '<=', $date)").replace("->where('end_date', '>=', $date)", "->whereDate('end_date', '>=', $date)"))

def controller(s):
    s=s.replace("$hasImportedIds = [];", "$semesters = Semester::orderByDesc('start_date')->get()->map(fn ($semester) => [\n            'id' => $semester->id, 'label' => $semester->display_name,\n            'start_date' => $semester->start_date->toDateString(), 'end_date' => $semester->end_date->toDateString(),\n        ]);\n        $importedByRoom = CourseSchedule::where('type', 'course')->get(['classroom_id', 'semester_id'])->groupBy('classroom_id');\n        $hasImportedIds = [];")
    s=s.replace('use ($hasImportedIds)', 'use ($hasImportedIds, $importedByRoom)')
    s=s.replace('$room->has_imported =', "$room->imported_semester_ids = ($importedByRoom->get($room->id) ?? collect())->pluck('semester_id')->unique()->values()->all();\n                $room->has_imported =")
    s=s.replace("'classrooms' => $classrooms,", "'semesters' => $semesters,\n            'defaultSemesterId' => $importSemester?->id,\n            'recordClassrooms' => Classroom::orderBy('code')->get(['id', 'code', 'name']),\n            'classrooms' => $classrooms,",1)
    s=s.replace("'classroom_ids' => ['required', 'array', 'min:1'],", "'semester_id' => ['required', 'integer', 'exists:semesters,id'],\n            'classroom_ids' => ['required', 'array', 'min:1'],")
    s=s.replace('$semester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();', "$semester = Semester::findOrFail($validated['semester_id']);")
    s=s.replace('$semesterStartDate = $semester->start_date', "$this->longTermCourseScheduleService->assertImportAvailable($semester, $importedSchedules);\n\n        $semesterStartDate = $semester->start_date")
    s=s.replace('public function revokeClassroomImport(Classroom $classroom)', 'public function revokeClassroomImport(Request $request, Classroom $classroom)')
    s=s.replace('$targetSemester = $this->longTermCourseScheduleService->resolveCurrentOrNearestFutureSemester();', "$validated = $request->validate(['semester_id' => ['required', 'integer', 'exists:semesters,id']]);\n        $targetSemester = Semester::findOrFail($validated['semester_id']);")
    s=s.replace('return back()->with(\'success\', "已撤回 {$classroom->code} 的課表匯入，共刪除 {$deleted} 筆。");', 'return response()->json([\'message\' => "已撤回 {$classroom->code} 的課表匯入，共刪除 {$deleted} 筆。"]);')
    return s
edit('app/Http/Controllers/AdminLongTermBorrowingController.php', controller)

def service(s):
    s=s.replace('use Illuminate\\Support\\Str;', 'use Illuminate\\Support\\Str;\nuse Illuminate\\Validation\\ValidationException;')
    s=s.replace('    public function replaceSemesterSchedulesForClassrooms', '''    public function assertImportAvailable(Semester $semester, array $rows): void
    {
        if (!$rows) {
            throw ValidationException::withMessages(['import' => '未取得課表，既有資料不會清除；如需清空請使用撤回。']);
        }
        $management = app(LongTermScheduleManagementService::class);
        foreach ($rows as $row) {
            $management->assertAvailable($row, $semester, null, true);
        }
    }

    public function replaceSemesterSchedulesForClassrooms''')
    s=s.replace('DB::transaction(function () use ($semester, $classroomIds, $rows): void {', '''DB::transaction(function () use ($semester, $classroomIds, $rows): void {
            Classroom::whereIn('id', $classroomIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($rows as $row) {
                if ((int) $row['semester_id'] !== (int) $semester->id || !$classroomIds->contains((int) $row['classroom_id'])
                    || $row['start_date'] < $semester->start_date->toDateString() || $row['end_date'] > $semester->end_date->toDateString()) {
                    throw ValidationException::withMessages(['import' => '課表超出所選學期或教室範圍。']);
                }
            }
            $this->assertImportAvailable($semester, $rows);''')
    s=s.replace("->whereIn('classroom_id', $classroomIds->all())\n                ->delete();", "->whereIn('classroom_id', $classroomIds->all())\n                ->where('type', 'course')\n                ->delete();")
    return s
edit('app/Services/Admin/LongTermCourseScheduleService.php',service)

def routes(s):
    s=s.replace('use App\\Http\\Controllers\\AdminAuthController;', 'use App\\Http\\Controllers\\AdminAuthController;\nuse App\\Http\\Controllers\\AdminLongTermRecordController;')
    lines=s.splitlines(); moved=[]; kept=[]
    for line in lines:
        if "Route::" in line and "'/long-term-borrowing" in line:
            moved.append(line.strip())
        else: kept.append(line)
    s='\n'.join(kept)+'\n'
    insert='\n'.join('        '+line for line in moved)+'''
        Route::get('/long-term-borrowing/records', [AdminLongTermRecordController::class, 'index'])->name('longTermBorrowing.records');
        Route::patch('/long-term-borrowing/records/{schedule}', [AdminLongTermRecordController::class, 'update'])->name('longTermBorrowing.records.update');
        Route::delete('/long-term-borrowing/records/{schedule}', [AdminLongTermRecordController::class, 'destroy'])->name('longTermBorrowing.records.destroy');

'''
    return s.replace('        Route::middleware(EnsureCurrentSemesterConfigured::class)',insert+'        Route::middleware(EnsureCurrentSemesterConfigured::class)')
edit('routes/admin.php',routes)
