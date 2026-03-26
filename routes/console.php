<?php

use App\Mail\BookingSubmitted;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('booking:test-mail
    {email : 收件信箱}
    {--date= : 借用日期，預設為今天}
    {--room= : 教室代號，未提供時使用第一間啟用教室}
    {--slots=* : 時段名稱，可傳多個，例如 --slots=1 --slots=2}
    {--name=測試使用者 : 申請人姓名}
    {--teacher=測試老師 : 指導老師}
    {--reason=這是一封測試申請通知信 : 借用事由}', function () {
    $email = (string) $this->argument('email');
    $date = $this->option('date') ?: now()->format('Y-m-d');
    $roomCode = $this->option('room');
    $slotNames = collect($this->option('slots'))->filter()->values();

    $classroomQuery = Classroom::query()->where('is_active', true);
    if ($roomCode) {
        $classroomQuery->where('code', $roomCode);
    }

    $classroom = $classroomQuery->orderBy('code')->first();

    if (! $classroom) {
        $this->error('找不到可用教室，請確認 --room 參數或資料表內容。');
        return self::FAILURE;
    }

    $timeSlotsQuery = TimeSlot::query()->orderBy('start_time');
    if ($slotNames->isNotEmpty()) {
        $timeSlotsQuery->whereIn('name', $slotNames->all());
    }

    $timeSlots = $timeSlotsQuery->get();

    if ($timeSlots->isEmpty()) {
        $this->error('找不到任何時段資料，請先確認 time_slots 資料表。');
        return self::FAILURE;
    }

    if ($slotNames->isEmpty()) {
        $timeSlots = $timeSlots->take(3)->values();
    }

    $borrower = new Borrower([
        'name' => (string) $this->option('name'),
        'email' => $email,
        'identity_code' => 'TEST0001',
        'phone' => '0912345678',
        'department' => '測試用',
    ]);

    $booking = new Booking([
        'teacher' => (string) $this->option('teacher'),
        'reason' => (string) $this->option('reason'),
        'status_enum' => 'pending',
    ]);

    $bookingDate = new BookingDate([
        'date' => $date,
    ]);
    $bookingDate->setRelation('timeSlots', $timeSlots);

    $booking->setRelation('borrower', $borrower);
    $booking->setRelation('classroom', $classroom);
    $booking->setRelation('bookingDates', collect([$bookingDate]));

    $mailable = new BookingSubmitted($booking, $timeSlots->pluck('name')->all());
    $html = $mailable->render();
    $subject = $mailable->envelope()->subject;

    Mail::send([], [], function ($message) use ($email, $html, $subject) {
        $message->to($email)
            ->subject($subject)
            ->html($html);
    });

    $this->info('測試信已送出。');
    $this->line('收件者: ' . $email);
    $this->line('教室: ' . $classroom->code . ' ' . $classroom->name);
    $this->line('日期: ' . $date);
    $this->line('時段: ' . $timeSlots->pluck('name')->implode('、'));

    return self::SUCCESS;
})->purpose('Send a booking submission test email without creating a booking');

Artisan::command('system:check-conflicts {--date-from=} {--date-to=}', function () {
    $from = (string) ($this->option('date-from') ?: now()->subMonths(3)->toDateString());
    $to = (string) ($this->option('date-to') ?: now()->addMonths(6)->toDateString());
    $hasBookingDeletedAt = Schema::hasColumn('bookings', 'deleted_at');
    $hasCourseScheduleDeletedAt = Schema::hasColumn('course_schedules', 'deleted_at');

    $this->info('Checking booking conflicts in date range: ' . $from . ' ~ ' . $to);

    $bookingConflicts = DB::table('booking_date_time_slot as bdts')
        ->join('booking_dates as bd', 'bd.id', '=', 'bdts.booking_date_id')
        ->join('bookings as b', 'b.id', '=', 'bd.booking_id')
        ->join('time_slots as ts', 'ts.id', '=', 'bdts.time_slot_id')
        ->whereBetween('bd.date', [$from, $to])
        ->whereIn('b.status_enum', Booking::activeStatusEnums())
        ->when($hasBookingDeletedAt, fn ($query) => $query->whereNull('b.deleted_at'))
        ->selectRaw('b.classroom_id, bd.date, bdts.time_slot_id, ts.name as slot_name, COUNT(*) as conflict_count, GROUP_CONCAT(b.id ORDER BY b.id) as booking_ids')
        ->groupBy('b.classroom_id', 'bd.date', 'bdts.time_slot_id', 'ts.name')
        ->havingRaw('COUNT(*) > 1')
        ->orderBy('bd.date')
        ->orderBy('b.classroom_id')
        ->get();

    if ($bookingConflicts->isEmpty()) {
        $this->info('No booking conflicts detected.');
    } else {
        $this->error('Booking conflicts found: ' . $bookingConflicts->count());
        $this->table(
            ['classroom_id', 'date', 'slot', 'count', 'booking_ids'],
            $bookingConflicts->map(fn ($row) => [
                $row->classroom_id,
                $row->date,
                $row->slot_name,
                $row->conflict_count,
                $row->booking_ids,
            ])->all()
        );
    }

    $this->newLine();
    $this->info('Checking course schedule conflicts by semester/day/classroom...');

    $courseConflicts = DB::table('course_schedule_time_slots as csts')
        ->join('course_schedules as cs', 'cs.id', '=', 'csts.course_schedule_id')
        ->join('time_slots as ts', 'ts.id', '=', 'csts.time_slot_id')
        ->when($hasCourseScheduleDeletedAt, fn ($query) => $query->whereNull('cs.deleted_at'))
        ->selectRaw('cs.semester_id, cs.classroom_id, cs.day_of_week, csts.time_slot_id, ts.name as slot_name, COUNT(*) as conflict_count, GROUP_CONCAT(cs.id ORDER BY cs.id) as schedule_ids')
        ->groupBy('cs.semester_id', 'cs.classroom_id', 'cs.day_of_week', 'csts.time_slot_id', 'ts.name')
        ->havingRaw('COUNT(*) > 1')
        ->orderBy('cs.semester_id')
        ->orderBy('cs.classroom_id')
        ->orderBy('cs.day_of_week')
        ->get();

    if ($courseConflicts->isEmpty()) {
        $this->info('No course schedule slot conflicts detected.');
    } else {
        $this->error('Course schedule slot conflicts found: ' . $courseConflicts->count());
        $this->table(
            ['semester_id', 'classroom_id', 'day_of_week', 'slot', 'count', 'schedule_ids'],
            $courseConflicts->map(fn ($row) => [
                $row->semester_id,
                $row->classroom_id,
                $row->day_of_week,
                $row->slot_name,
                $row->conflict_count,
                $row->schedule_ids,
            ])->all()
        );
    }

    return ($bookingConflicts->isEmpty() && $courseConflicts->isEmpty()) ? self::SUCCESS : self::FAILURE;
})->purpose('Check booking and course-schedule slot conflicts in current data');
