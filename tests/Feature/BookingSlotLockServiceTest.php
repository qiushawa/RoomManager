<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\Manager;
use App\Models\Semester;
use App\Models\TimeSlot;
use App\Services\BookingSlotLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingSlotLockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_booking_writes_slot_locks(): void
    {
        Mail::fake();

        $classroom = Classroom::factory()->create(['code' => 'BGC101']);
        $slotA = TimeSlot::factory()->create(['name' => '1', 'start_time' => '08:10:00', 'end_time' => '09:00:00']);
        $slotB = TimeSlot::factory()->create(['name' => '2', 'start_time' => '09:10:00', 'end_time' => '10:00:00']);
        $date = now()->addDay()->toDateString();

        $response = $this->post(route('home.store'), [
            'classroom_id' => $classroom->id,
            'classroom_code' => $classroom->code,
            'selections' => [
                [
                    'date' => $date,
                    'time_slot_ids' => [$slotA->id, $slotB->id],
                ],
            ],
            'applicant' => [
                'name' => 'Test User',
                'identity_code' => 'A1234567',
                'email' => 'test-user@example.com',
                'phone' => '0912345678',
                'department' => '資訊工程系',
                'teacher' => '王老師',
                'reason' => '單元測試',
            ],
        ]);

        $response->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('booking_slot_locks', [
            'booking_id' => $booking->id,
            'classroom_id' => $classroom->id,
            'date' => $date,
            'time_slot_id' => $slotA->id,
        ]);

        $this->assertDatabaseHas('booking_slot_locks', [
            'booking_id' => $booking->id,
            'classroom_id' => $classroom->id,
            'date' => $date,
            'time_slot_id' => $slotB->id,
        ]);
    }

    public function test_cancel_booking_removes_slot_locks(): void
    {
        $booking = $this->createPendingBookingWithLocks();

        $this->assertSame(2, DB::table('booking_slot_locks')->where('booking_id', $booking->id)->count());

        $url = URL::signedRoute('bookings.cancel.destroy', ['booking' => $booking->id]);
        $response = $this->post($url);

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status_enum);
        $this->assertSame(0, DB::table('booking_slot_locks')->where('booking_id', $booking->id)->count());
    }

    public function test_review_approve_keeps_slot_locks(): void
    {
        Semester::query()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
        ]);

        $manager = Manager::query()->forceCreate([
            'username' => 'admin_test',
            'password' => Hash::make('secret123'),
            'name' => 'Admin Tester',
            'email' => 'admin-test@example.com',
        ]);

        $booking = $this->createPendingBookingWithLocks();
        $beforeCount = DB::table('booking_slot_locks')->where('booking_id', $booking->id)->count();

        $response = $this
            ->actingAs($manager, 'admin')
            ->patch(route('admin.bookings.updateStatus', ['booking' => $booking->id]), [
                'status' => 1,
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('approved', $booking->status_enum);
        $this->assertSame($manager->id, $booking->approved_by);
        $this->assertSame($beforeCount, DB::table('booking_slot_locks')->where('booking_id', $booking->id)->count());
    }

    private function createPendingBookingWithLocks(): Booking
    {
        $borrower = Borrower::factory()->create();
        $classroom = Classroom::factory()->create();
        $slotA = TimeSlot::factory()->create(['name' => '3', 'start_time' => '10:10:00', 'end_time' => '11:00:00']);
        $slotB = TimeSlot::factory()->create(['name' => '4', 'start_time' => '11:10:00', 'end_time' => '12:00:00']);

        $booking = Booking::query()->create([
            'borrower_id' => $borrower->id,
            'classroom_id' => $classroom->id,
            'reason' => '測試鎖同步',
            'teacher' => '測試老師',
            'status_enum' => 'pending',
        ]);

        $bookingDate = $booking->bookingDates()->create([
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $bookingDate->timeSlots()->sync([$slotA->id, $slotB->id]);

        app(BookingSlotLockService::class)->syncForBooking($booking);

        return $booking->fresh(['bookingDates.timeSlots']);
    }
}
