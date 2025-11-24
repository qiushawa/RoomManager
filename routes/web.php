<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});


Route::get('kuanyuu', function () {
    return Inertia::render('Kuanyuu');
});



// classrooms
Route::get('/classroom', function () {
    $classroom = [
        [
            'name' => '綜三館BGC',
            'items' => [
                ['id' => 1, 'title' => 'BGC0305'],
                ['id' => 2, 'title' => 'BGC0402'],
                ['id' => 3, 'title' => 'BGC0501'],
                ['id' => 4, 'title' => 'BGC0508'],
                ['id' => 5, 'title' => 'BGC0513'],
                ['id' => 6, 'title' => 'BGC0601'],
            ],
        ],
        [
            'name' => '跨領域BCB',
            'items' => [
                ['id' => 7, 'title' => 'BCB0303'],
                ['id' => 8, 'title' => 'BCB0305'],
            ],
        ],
        [
            'name' => '科研大樓BRA',
            'items' => [
                ['id' => 9, 'title' => 'BRA0102'],
                ['id' => 10, 'title' => 'BRA0201'],
            ],
        ],
    ];

    // 使用 Inertia 回傳 Vue 頁面
    return Inertia::render('Classroom')->with('classrooms', $classroom);
})->name('classroom');