<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Marketplace\MarketplaceController::class, 'index'])->name('explore');
Route::get('/dashboard', \App\Http\Controllers\Marketplace\AccountDashboardController::class)->middleware('auth')->name('dashboard');
require __DIR__.'/marketplace.php';

Route::post('/support/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('support.store');

// Admin Panel (Inertia.js)
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [App\Http\Controllers\Admin\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\Admin\LoginController::class, 'login']);
    });

    // Authenticated routes
    Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Admin\LoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [App\Http\Controllers\Admin\UserListingsController::class, 'users'])->name('users.index');
        Route::get('/listings', [App\Http\Controllers\Admin\ListingInspectionController::class, 'spaces'])->name('listings.index');
        Route::get('/vehicles', [App\Http\Controllers\Admin\ListingInspectionController::class, 'vehicles'])->name('vehicles.index');
        Route::get('/services', [App\Http\Controllers\Admin\ListingInspectionController::class, 'services'])->name('services.index');
        Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/messages', [App\Http\Controllers\Admin\TicketController::class, 'message'])->middleware('throttle:60,1')->name('tickets.message');
        Route::get('/analytics', [App\Http\Controllers\Admin\DashboardController::class, 'analytics'])->name('analytics');
        
        // Redirect /admin to dashboard
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/avatar', [\App\Http\Controllers\AvatarController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('/profile/avatar', [\App\Http\Controllers\AvatarController::class, 'destroy']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::put('/account/onboarding', [\App\Http\Controllers\OnboardingController::class, 'update'])->middleware(['auth','throttle:20,1']);

require __DIR__.'/auth.php';
