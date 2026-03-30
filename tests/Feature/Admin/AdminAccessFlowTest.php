<?php

namespace Tests\Feature\Admin;

use App\Models\Manager;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_admin_route(): void
    {
        $response = $this->get(route('admin.settings'));

        $response->assertNotFound();
    }

    public function test_authenticated_admin_without_semester_is_redirected_to_settings_from_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_admin_with_semester_can_access_dashboard(): void
    {
        $admin = $this->createAdmin();
        $this->createCurrentSemester();

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    private function createAdmin(): Manager
    {
        return Manager::query()->forceCreate([
            'username' => 'admin_flow',
            'password' => Hash::make('secret123'),
            'name' => 'Admin Flow',
            'email' => 'admin-flow@example.com',
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
