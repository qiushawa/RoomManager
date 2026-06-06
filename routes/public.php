<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/Home');
});

Route::get('/Home', [HomeController::class, 'index'])->name('home.index');
Route::post('/bookings', [HomeController::class, 'store'])->name('home.store');
Route::get('/bookings/{booking}/cancel', [HomeController::class, 'showCancelConfirmation'])
    ->middleware('signed')
    ->name('bookings.cancel.confirm');
Route::post('/bookings/{booking}/cancel', [HomeController::class, 'destroy'])
    ->middleware('signed')
    ->name('bookings.cancel.destroy');
