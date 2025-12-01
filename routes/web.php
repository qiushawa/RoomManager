<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $classrooms = [
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
    return Inertia::render('Home')->with('classrooms', $classrooms);
});