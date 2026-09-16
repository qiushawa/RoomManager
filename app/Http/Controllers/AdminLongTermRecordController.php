<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\CourseSchedule;
use App\Services\Admin\LongTermCourseScheduleService;
use App\Services\Admin\LongTermScheduleManagementService;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLongTermRecordController extends Controller
{
    public function manualAvailability(Request $request, RoomAvailabilityService $availability)
    {
        abort_unless(\App\Models\Semester::findByDate(now()), 422, '目前沒有設定中的學期，請先建立學期資料。');
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        return response()->json(['occupied_data' => $availability->getRecurringOccupiedData((int) $data['classroom_id'], Carbon::parse($data['start_date']), Carbon::parse($data['end_date']))]);
    }

    public function availability(Request $request, CourseSchedule $schedule, RoomAvailabilityService $availability)
    {
        $semester = $schedule->semester;
        abort_unless($semester, 422, '紀錄缺少所屬學期。');
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$semester->start_date->toDateString(), 'before_or_equal:'.$semester->end_date->toDateString()],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.$semester->end_date->toDateString()],
        ]);
        return response()->json(['occupied_data' => $availability->getRecurringOccupiedData((int) $data['classroom_id'], Carbon::parse($data['start_date']), Carbon::parse($data['end_date']), $schedule->id)]);
    }

    public function index(Request $request, LongTermCourseScheduleService $imports)
    {
        $filters = $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'type' => ['nullable', 'in:course,manual,borrowed'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'building' => ['nullable', 'string', 'max:30'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $query = CourseSchedule::with(['semester', 'classroom', 'timeSlots']);
        foreach (['semester_id', 'type', 'classroom_id', 'day_of_week'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['building'])) {
            $ids = Classroom::all(['id', 'code'])->filter(fn ($room) => $imports->extractBuildingCode($room->code) === $filters['building'])->modelKeys();
            $query->whereIn('classroom_id', $ids);
        }
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('course_name', 'like', $search)->orWhere('class_name', 'like', $search)->orWhere('teacher_name', 'like', $search)
                ->orWhereHas('classroom', fn ($r) => $r->where('code', 'like', $search)->orWhere('name', 'like', $search)));
        }

        return response()->json($query
            ->orderBy('course_name')
            ->orderBy('class_name')
            ->orderByDesc('semester_id')
            ->orderBy('teacher_name')
            ->orderBy('classroom_id')
            ->orderBy('day_of_week')
            ->orderBy('id')
            ->paginate(20)->withQueryString());
    }

    public function update(Request $request, CourseSchedule $schedule, LongTermScheduleManagementService $management)
    {
        $schedule->load('semester', 'timeSlots');
        $semester = $schedule->semester;
        abort_unless($semester, 422, '紀錄缺少所屬學期。');
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'course_name' => ['required', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'teacher_name' => ['nullable', 'string', 'max:50'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$semester->start_date->toDateString(), 'before_or_equal:'.$semester->end_date->toDateString()],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.$semester->end_date->toDateString()],
            'time_slot_ids' => ['required', 'array', 'min:1'],
            'time_slot_ids.*' => ['required', 'integer', 'distinct', 'exists:time_slots,id'],
        ]);
        DB::transaction(function () use ($schedule, $data, $semester, $management) {
            Classroom::whereIn('id', [$schedule->classroom_id, $data['classroom_id']])->orderBy('id')->lockForUpdate()->get();
            $schedule->refresh()->load('timeSlots');
            $changed = (int) $schedule->classroom_id !== (int) $data['classroom_id']
                || (int) $schedule->day_of_week !== (int) $data['day_of_week']
                || ($schedule->start_date ?? $semester->start_date)->toDateString() !== $data['start_date']
                || ($schedule->end_date ?? $semester->end_date)->toDateString() !== $data['end_date']
                || collect($schedule->timeSlots->modelKeys())->sort()->values()->all() !== collect($data['time_slot_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($changed) {
                $management->assertAvailable($data, $semester, $schedule->id);
            }
            $schedule->fill(collect($data)->except('time_slot_ids')->all())->save();
            $schedule->timeSlots()->sync($data['time_slot_ids']);
        });

        return response()->json(['message' => '長期借用紀錄已更新。']);
    }

    public function destroy(CourseSchedule $schedule)
    {
        DB::transaction(function () use ($schedule) {
            Classroom::whereKey($schedule->classroom_id)->lockForUpdate()->first();
            $schedule->timeSlots()->detach();
            $schedule->delete();
        });

        return response()->json(['message' => '長期借用紀錄已刪除。']);
    }
}
