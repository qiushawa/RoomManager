<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $buildings = [
        [
            'name' => '綜三館BGC',
            'classrooms' => [
                ['id' => 1, 'name' => 'BGC0305'],
                ['id' => 2, 'name' => 'BGC0402'],
                ['id' => 3, 'name' => 'BGC0501'],
                ['id' => 4, 'name' => 'BGC0508'],
                ['id' => 5, 'name' => 'BGC0513'],
                ['id' => 6, 'name' => 'BGC0601'],
            ],
        ],
        [
            'name' => '跨領域BCB',
            'classrooms' => [
                ['id' => 7, 'name' => 'BCB0303'],
                ['id' => 8, 'name' => 'BCB0305'],
            ],
        ],
        [
            'name' => '科研大樓BRA',
            'classrooms' => [
                ['id' => 9, 'name' => 'BRA0102'],
                ['id' => 10, 'name' => 'BRA0201'],
            ],
        ],
    ];
    return Inertia::render('Home')->with('buildings', $buildings);
});
