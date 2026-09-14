<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::capture());
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if (!$app->environment('testing') || config('database.connections.mysql.database') !== 'room_test') {
    throw new RuntimeException('QA must use room_test.');
}
if (PHP_SAPI === 'cli') {
    $semester = App\Models\Semester::create(['academic_year' => 115, 'semester' => 1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-31']);
    App\Models\Semester::create(['academic_year' => 114, 'semester' => 2, 'start_date' => '2026-02-01', 'end_date' => '2026-06-30']);
    $room = App\Models\Classroom::factory()->create(['code' => 'BGC-QA', 'name' => '測試教室', 'is_active' => true]);
    $slots = collect([1, 2, 3])->map(fn ($n) => App\Models\TimeSlot::factory()->create(['name' => (string) $n, 'start_time' => sprintf('%02d:00', 7+$n), 'end_time' => sprintf('%02d:00', 8+$n)]));
    foreach (['course', 'manual', 'borrowed'] as $i => $type) {
        $row = App\Models\CourseSchedule::create(['semester_id' => $semester->id, 'classroom_id' => $room->id, 'type' => $type, 'course_name' => '介面驗證 '.($i+1), 'teacher_name' => '測試教師', 'day_of_week' => $i+1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-31']);
        $row->timeSlots()->sync([$slots[0]->id, $slots[2]->id]);
    }
    App\Models\Manager::forceCreate(['username' => 'qa-local-only', 'name' => 'QA', 'password' => bcrypt(bin2hex(random_bytes(20))), 'email' => 'qa@example.test']);
    echo "QA fixtures ready\n";
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/build/')) return false;
Illuminate\Support\Facades\URL::forceScheme('http');
auth()->guard('admin')->setUser(App\Models\Manager::where('username', 'qa-local-only')->firstOrFail());
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
