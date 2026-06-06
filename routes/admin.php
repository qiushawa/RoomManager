<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminBlacklistController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminClassroomController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminLongTermBorrowingController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Middleware\EnsureCurrentSemesterConfigured;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'login'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'authenticate'])->name('authenticate');

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/settings', [AdminSettingsController::class, 'settings'])->name('settings');
        Route::post('/settings/semesters', [AdminSettingsController::class, 'storeSemester'])->name('settings.semesters.store');

        Route::middleware(EnsureCurrentSemesterConfigured::class)->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');

            Route::get('/bookings', [AdminBookingController::class, 'bookings'])->name('bookings');
            Route::get('/reviews', [AdminBookingController::class, 'reviews'])->name('reviews');
            Route::get('/borrowing-records', [AdminBookingController::class, 'borrowingRecords'])->name('borrowingRecords');
            Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateBookingStatus'])->name('bookings.updateStatus');
            Route::get('/notifications', [AdminBookingController::class, 'notifications'])->name('notifications');

            Route::prefix('rooms')->group(function () {
                Route::get('/', [AdminClassroomController::class, 'rooms'])->name('rooms');
                Route::post('/', [AdminClassroomController::class, 'storeRoom'])->name('rooms.store');
                Route::patch('/batch', [AdminClassroomController::class, 'batchUpdateRooms'])->name('rooms.batch');
                Route::patch('/{classroom}/toggle', [AdminClassroomController::class, 'toggleRoom'])->name('rooms.toggle');
                Route::delete('/{classroom}', [AdminClassroomController::class, 'destroyRoom'])->name('rooms.destroy');
            });

            Route::prefix('users')->group(function () {
                Route::get('/', [AdminBlacklistController::class, 'users'])->name('users');
                Route::post('/blacklist', [AdminBlacklistController::class, 'storeBlacklist'])->name('users.blacklist.store');
            });

            Route::get('/long-term-borrowing', [AdminLongTermBorrowingController::class, 'longTermBorrowing'])->name('longTermBorrowing');
            Route::post('/long-term-borrowing/import', [AdminLongTermBorrowingController::class, 'importCourseSchedules'])->name('longTermBorrowing.import');
            Route::post('/long-term-borrowing/preview', [AdminLongTermBorrowingController::class, 'previewCourseSchedules'])->name('longTermBorrowing.preview');
            Route::post('/long-term-borrowing/manual/conflicts', [AdminLongTermBorrowingController::class, 'previewManualLongTermBorrowingConflicts'])->name('longTermBorrowing.manual.conflicts');
            Route::post('/long-term-borrowing/manual/resolve-conflict', [AdminLongTermBorrowingController::class, 'resolveManualLongTermConflict'])->name('longTermBorrowing.manual.resolveConflict');
            Route::post('/long-term-borrowing/manual', [AdminLongTermBorrowingController::class, 'storeManualLongTermBorrowing'])->name('longTermBorrowing.manual');
            Route::delete('/long-term-borrowing/manual/{schedule}', [AdminLongTermBorrowingController::class, 'revokeManualLongTermBorrowing'])->name('longTermBorrowing.manual.revoke');
            Route::delete('/long-term-borrowing/import/{classroom}', [AdminLongTermBorrowingController::class, 'revokeClassroomImport'])->name('longTermBorrowing.revoke');
        });
    });
});
