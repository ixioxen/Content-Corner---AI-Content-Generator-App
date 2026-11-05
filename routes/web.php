<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BrandProfileController;
use App\Http\Controllers\ContentGenerationController;
use App\Http\Controllers\CustomToneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeneratedContentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('generate', [ContentGenerationController::class, 'create'])->name('generations.create');
    Route::post('generate', [ContentGenerationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('generations.store');
    Route::get('generations', [GeneratedContentController::class, 'index'])->name('generations.index');
    Route::get('generations/{generation}', [GeneratedContentController::class, 'show'])->name('generations.show');
    Route::delete('generations/{generation}', [GeneratedContentController::class, 'destroy'])->name('generations.destroy');
    Route::post('brands/undo/{token}', [BrandProfileController::class, 'undo'])->name('brands.undo');
    Route::resource('brands', BrandProfileController::class)->except('show');
    Route::post('tones/undo/{token}', [CustomToneController::class, 'undo'])->name('tones.undo');
    Route::resource('tones', CustomToneController::class)->except('show');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('overview');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/credits', [AdminUserController::class, 'adjustCredits'])->name('users.credits');
        Route::get('/transactions', AdminTransactionController::class)->name('transactions.index');
        Route::get('/audit-logs', AdminAuditLogController::class)->name('audit-logs.index');
    });
});

require __DIR__.'/settings.php';
