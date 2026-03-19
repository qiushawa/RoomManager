<?php

use App\Mail\BookingSubmitted;
use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\TimeSlot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

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
        'date' => $date,
        'teacher' => (string) $this->option('teacher'),
        'reason' => (string) $this->option('reason'),
        'status' => 0,
    ]);

    $booking->setRelation('borrower', $borrower);
    $booking->setRelation('classroom', $classroom);
    $booking->setRelation('timeSlots', $timeSlots);

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
