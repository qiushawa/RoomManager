<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 後台管理路由，統一使用 /admin 前綴
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // 儀表板
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // 所有路徑都重新導向到儀表板，讓前端路由處理 (暫時)
    Route::get('/{any}', [DashboardController::class,'index'])->where('any', '.*')->name('dashboard');
});
