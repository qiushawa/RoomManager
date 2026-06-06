<?php

namespace Tests\Feature\Admin;

use App\Models\Blacklist;
use App\Models\BlacklistReason;
use App\Models\Borrower;
use App\Models\Classroom;
use App\Models\Manager;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_semester_from_settings_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'admin')
            ->post(route('admin.settings.semesters.store'), [
                'academic_year' => 115,
                'semester' => 1,
                'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('semesters', [
            'academic_year' => 115,
            'semester' => 1,
        ]);
    }

    public function test_admin_can_create_and_toggle_classroom(): void
    {
        $admin = $this->createAdmin();
        $this->createCurrentSemester();

        $createResponse = $this
            ->actingAs($admin, 'admin')
            ->post(route('admin.rooms.store'), [
                'code' => 'GC101',
                'name' => 'Room 101',
            ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'code' => 'GC101',
            'name' => 'Room 101',
            'is_active' => 1,
        ]);

        $classroom = Classroom::query()->where('code', 'GC101')->firstOrFail();

        $toggleResponse = $this
            ->actingAs($admin, 'admin')
            ->patch(route('admin.rooms.toggle', ['classroom' => $classroom->id]));

        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_batch_update_classrooms_and_rename_single_room(): void
    {
        $admin = $this->createAdmin();
        $this->createCurrentSemester();

        $roomA = Classroom::factory()->create([
            'code' => 'GC201',
            'name' => 'Room A',
            'is_active' => false,
        ]);
        $roomB = Classroom::factory()->create([
            'code' => 'GC202',
            'name' => 'Room B',
            'is_active' => true,
        ]);

        $enableResponse = $this
            ->actingAs($admin, 'admin')
            ->patch(route('admin.rooms.batch'), [
                'action' => 'enable',
                'selected_ids' => [$roomA->id, $roomB->id],
            ]);

        $enableResponse->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['id' => $roomA->id, 'is_active' => 1]);
        $this->assertDatabaseHas('classrooms', ['id' => $roomB->id, 'is_active' => 1]);

        $disableResponse = $this
            ->actingAs($admin, 'admin')
            ->patch(route('admin.rooms.batch'), [
                'action' => 'disable',
                'selected_ids' => [$roomA->id, $roomB->id],
            ]);

        $disableResponse->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['id' => $roomA->id, 'is_active' => 0]);
        $this->assertDatabaseHas('classrooms', ['id' => $roomB->id, 'is_active' => 0]);

        $renameResponse = $this
            ->actingAs($admin, 'admin')
            ->patch(route('admin.rooms.batch'), [
                'action' => 'rename',
                'selected_ids' => [$roomA->id],
                'name' => 'Room A Updated',
            ]);

        $renameResponse->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'id' => $roomA->id,
            'name' => 'Room A Updated',
        ]);

        $invalidRenameResponse = $this
            ->actingAs($admin, 'admin')
            ->from(route('admin.rooms'))
            ->patch(route('admin.rooms.batch'), [
                'action' => 'rename',
                'selected_ids' => [$roomA->id, $roomB->id],
                'name' => 'Should Fail',
            ]);

        $invalidRenameResponse->assertRedirect(route('admin.rooms'));
        $invalidRenameResponse->assertSessionHasErrors('operation');
    }

    public function test_admin_can_filter_classrooms_by_status_and_search(): void
    {
        $admin = $this->createAdmin();
        $this->createCurrentSemester();

        Classroom::factory()->create([
            'code' => 'GC301',
            'name' => 'Enabled Room',
            'is_active' => true,
        ]);
        Classroom::factory()->create([
            'code' => 'GC302',
            'name' => 'Disabled Room',
            'is_active' => false,
        ]);

        $statusResponse = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.rooms', ['status' => 'enabled']));

        $statusResponse->assertOk();
        $statusResponse->assertSee('GC301');
        $statusResponse->assertDontSee('GC302');

        $searchResponse = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.rooms', ['search' => 'Disabled Room']));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Disabled Room');
        $searchResponse->assertDontSee('Enabled Room');
    }

    public function test_admin_can_add_borrower_to_blacklist_with_reasons(): void
    {
        $admin = $this->createAdmin();
        $this->createCurrentSemester();

        $borrower = Borrower::factory()->create();
        $reasonA = BlacklistReason::factory()->create(['reason' => 'late return']);
        $reasonB = BlacklistReason::factory()->create(['reason' => 'damage']);

        $response = $this
            ->actingAs($admin, 'admin')
            ->post(route('admin.users.blacklist.store'), [
                'identity_code' => $borrower->identity_code,
                'reason_ids' => [$reasonA->id, $reasonB->id],
                'banned_until' => now()->addMonths(2)->toDateString(),
            ]);

        $response->assertRedirect();

        $blacklist = Blacklist::query()
            ->where('borrower_id', $borrower->id)
            ->first();

        $this->assertNotNull($blacklist);
        $this->assertDatabaseCount('blacklist_details', 2);
    }

    private function createAdmin(): Manager
    {
        return Manager::query()->forceCreate([
            'username' => 'admin_manage',
            'password' => Hash::make('secret123'),
            'name' => 'Admin Manage',
            'email' => 'admin-manage@example.com',
        ]);
    }

    private function createCurrentSemester(): Semester
    {
        return Semester::query()->create([
            'academic_year' => 114,
            'semester' => 2,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);
    }
}
