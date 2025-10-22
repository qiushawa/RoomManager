<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});


// kuanyu
Route::get('/kuanyu', function () {
    
    return Inertia::render('Kuanyu');
})->name('kuanyu');