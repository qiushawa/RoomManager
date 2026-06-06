<?php

namespace Tests\Feature\Routes;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminBlacklistController;
use App\Http\Controllers\AdminClassroomController;
use App\Http\Controllers\AdminLongTermBorrowingController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebRouteContractTest extends TestCase
{
    public function test_named_routes_keep_uri_method_and_action_contracts(): void
    {
        $this->assertRouteContract('home.index', 'Home', 'GET', HomeController::class . '@index');
        $this->assertRouteContract('home.store', 'bookings', 'POST', HomeController::class . '@store');
        $this->assertRouteContract('admin.login', 'admin/login', 'GET', AdminAuthController::class . '@login');
        $this->assertRouteContract('admin.authenticate', 'admin/login', 'POST', AdminAuthController::class . '@authenticate');
        $this->assertRouteContract('admin.settings', 'admin/settings', 'GET', AdminSettingsController::class . '@settings');
        $this->assertRouteContract('admin.rooms.store', 'admin/rooms', 'POST', AdminClassroomController::class . '@storeRoom');
        $this->assertRouteContract('admin.users.blacklist.store', 'admin/users/blacklist', 'POST', AdminBlacklistController::class . '@storeBlacklist');
        $this->assertRouteContract('admin.longTermBorrowing.manual.revoke', 'admin/long-term-borrowing/manual/{schedule}', 'DELETE', AdminLongTermBorrowingController::class . '@revokeManualLongTermBorrowing');
    }

    private function assertRouteContract(string $name, string $uri, string $method, string $action): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] is missing.");
        $this->assertSame($uri, $route->uri(), "Route [{$name}] uri changed.");
        $this->assertContains(strtoupper($method), $route->methods(), "Route [{$name}] method changed.");
        $this->assertSame($action, $route->getActionName(), "Route [{$name}] action changed.");
    }
}
