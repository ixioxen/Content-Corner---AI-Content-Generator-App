<?php

use App\Http\Controllers\CustomToneController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('tones', CustomToneController::class)->except('show');
});

require __DIR__.'/settings.php';
