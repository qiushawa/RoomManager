<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\Booking;
use App\Models\Borrower;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 示範
        return Inertia::render('Admin/Dashboard', [
            'meta' => [
                'title' => '儀表板',
            ],
            'defer' => Inertia::defer(fn () => '延遲載入的資料'),
            'lazy' => Inertia::lazy(fn () => '需主動觸發載入的資料'),
        ]);
    }
}
