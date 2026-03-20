<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use Inertia\Inertia;

// 1. 首頁導向改為 /Home
Route::get('/', function () {
    return redirect('/Home');
});

// 2. 更改主要路由為 /Home
// 注意：URL 大小寫通常視為不同，這裡依您的需求設定為 /Home
Route::get('/Home', [HomeController::class, 'index'])->name('home.index');
Route::post('/bookings', [HomeController::class, 'store'])->name('home.store');
Route::get('/bookings/{booking}/cancel', [HomeController::class, 'showCancelConfirmation'])
    ->middleware('signed')
    ->name('bookings.cancel.confirm');
Route::post('/bookings/{booking}/cancel', [HomeController::class, 'destroy'])
    ->middleware('signed')
    ->name('bookings.cancel.destroy');

// --- 管理員路由 ---
Route::prefix('admin')->name('admin.')->group(function () {
    // 訪客可存取
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'authenticate'])->name('authenticate');

    // 需要管理員權限
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings', [AdminController::class, 'bookings'])->name('bookings');
        Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
        Route::get('/borrowing-records', [AdminController::class, 'borrowingRecords'])->name('borrowingRecords');
        Route::patch('/bookings/{booking}/status', [AdminController::class, 'updateBookingStatus'])->name('bookings.updateStatus');
        Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
        Route::get('/rooms', [AdminController::class, 'rooms'])->name('rooms');
        Route::post('/rooms', [AdminController::class, 'storeRoom'])->name('rooms.store');
        Route::patch('/rooms/{classroom}/toggle', [AdminController::class, 'toggleRoom'])->name('rooms.toggle');
        Route::delete('/rooms/{classroom}', [AdminController::class, 'destroyRoom'])->name('rooms.destroy');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/blacklist', [AdminController::class, 'storeBlacklist'])->name('users.blacklist.store');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::get('/long-term-borrowing', [AdminController::class, 'longTermBorrowing'])->name('longTermBorrowing');
        Route::post('/long-term-borrowing/import', [AdminController::class, 'importCourseSchedules'])->name('longTermBorrowing.import');
        Route::post('/long-term-borrowing/preview', [AdminController::class, 'previewCourseSchedules'])->name('longTermBorrowing.preview');
        Route::post('/long-term-borrowing/manual/conflicts', [AdminController::class, 'previewManualLongTermBorrowingConflicts'])->name('longTermBorrowing.manual.conflicts');
        Route::post('/long-term-borrowing/manual', [AdminController::class, 'storeManualLongTermBorrowing'])->name('longTermBorrowing.manual');
        Route::delete('/long-term-borrowing/manual/{schedule}', [AdminController::class, 'revokeManualLongTermBorrowing'])->name('longTermBorrowing.manual.revoke');
        Route::delete('/long-term-borrowing/import/{classroom}', [AdminController::class, 'revokeClassroomImport'])->name('longTermBorrowing.revoke');
    });
});
