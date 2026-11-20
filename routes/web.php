<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminCreditPackageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BrandProfileController;
use App\Http\Controllers\ContentGenerationController;
use App\Http\Controllers\CustomToneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeneratedContentController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ScriptGenerationController;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\VerifyCreditPurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('billing', BillingController::class)->name('billing.index');
    Route::post('billing/purchases', StripeCheckoutController::class)
        ->middleware('throttle:5,1')
        ->name('billing.purchases.store');
    Route::post('billing/purchases/{purchase}/verify', VerifyCreditPurchaseController::class)
        ->middleware('throttle:10,1')
        ->name('billing.purchases.verify');
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

    // -------------------------------------------------------
    // Script Generator routes
    // -------------------------------------------------------
    // GET  /scripts          → index()      list all scripts (history)
    // GET  /scripts/create   → create()     show the generator form
    // POST /scripts          → store()      submit form & generate
    // GET  /scripts/{script} → show()       view a generated script
    // POST /scripts/{script}/regenerate → regenerate() generate again
    // -------------------------------------------------------
    Route::get('scripts', [ScriptGenerationController::class, 'index'])->name('scripts.index');
    Route::get('scripts/create', [ScriptGenerationController::class, 'create'])->name('scripts.create');
    Route::post('scripts', [ScriptGenerationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('scripts.store');
    Route::get('scripts/{script}', [ScriptGenerationController::class, 'show'])->name('scripts.show');
    Route::post('scripts/{script}/regenerate', [ScriptGenerationController::class, 'regenerate'])
        ->middleware('throttle:10,1')
        ->name('scripts.regenerate');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('overview');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/credits', [AdminUserController::class, 'adjustCredits'])->name('users.credits');
        Route::get('/transactions', AdminTransactionController::class)->name('transactions.index');
        Route::get('/audit-logs', AdminAuditLogController::class)->name('audit-logs.index');
        Route::get('/credit-packages', [AdminCreditPackageController::class, 'index'])->name('credit-packages.index');
        Route::post('/credit-packages', [AdminCreditPackageController::class, 'store'])->name('credit-packages.store');
        Route::patch('/credit-packages/{creditPackage}', [AdminCreditPackageController::class, 'update'])->name('credit-packages.update');
    });
});

require __DIR__.'/settings.php';
