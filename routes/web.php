<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\Marketplace\MarketplaceController::class, 'index'])->name('explore');
Route::get('/dashboard', fn () => redirect('/'))->middleware('auth')->name('dashboard');
require __DIR__.'/marketplace.php';

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
        Route::get('/users', [App\Http\Controllers\Admin\DashboardController::class, 'users'])->name('users.index');
        Route::get('/listings', [App\Http\Controllers\Admin\DashboardController::class, 'listings'])->name('listings.index');
        Route::get('/vehicles', [App\Http\Controllers\Admin\DashboardController::class, 'vehicleListings'])->name('vehicles.index');
        Route::get('/services', [App\Http\Controllers\Admin\DashboardController::class, 'serviceListings'])->name('services.index');
        Route::get('/tickets', [App\Http\Controllers\Admin\DashboardController::class, 'tickets'])->name('tickets.index');
        Route::get('/analytics', [App\Http\Controllers\Admin\DashboardController::class, 'analytics'])->name('analytics');
        
        // Redirect /admin to dashboard
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
    });
});

// Aadi Components Demo (Vue Components with Inertia.js)
Route::prefix('admin-demo')->name('admin.demo.')->group(function () {
    Route::get('/demo', function () {
        return Inertia::render('Admin/ComponentsDemo');
    })->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Component Demo Routes (using Blade components with Aadi branding)
Route::prefix('admin-demo')->name('admin.demo.')->group(function () {
    Route::get('/test', function () {
        return 'Test route works!';
    });
    
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    Route::get('/users', function () {
        return view('admin.examples.users-index');
    })->name('users');
});

require __DIR__.'/auth.php';
