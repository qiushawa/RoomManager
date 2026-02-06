<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

// 1. 首頁導向改為 /Home
Route::get('/', function () {
    return redirect('/Home');
});

// 2. 更改主要路由為 /Home
// 注意：URL 大小寫通常視為不同，這裡依您的需求設定為 /Home
Route::get('/Home', [HomeController::class, 'index'])->name('home.index');
Route::post('/bookings', [HomeController::class, 'store'])->name('home.store');

require __DIR__.'/admin.php';