<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $sitemap = rtrim(config('app.url'), '/').'/sitemap.xml';

    return response("User-agent: *\nDisallow:\nSitemap: {$sitemap}\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
Route::get('/sitemap.xml', function (\App\Services\PageAvailability $pages) {
    $base = rtrim(config('app.url'), '/');
    $urls = array_filter([
        $pages->enabled('home') ? $base.'/' : null,
        $pages->enabled('explore') ? $base.'/explore' : null,
        $base.'/privacy-policy', $base.'/terms-of-service', $base.'/data-deletion',
    ]);
    $entries = implode('', array_map(fn ($url) => '<url><loc>'.e($url).'</loc></url>', $urls));

    return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
});
Route::get('/', [\App\Http\Controllers\Marketplace\MarketplaceController::class, 'index'])->middleware('page.available:home')->name('explore');
Route::get('/privacy-policy', [\App\Http\Controllers\PublicLegalController::class, 'privacy'])->name('privacy-policy');
Route::get('/terms-of-service', [\App\Http\Controllers\PublicLegalController::class, 'terms'])->name('terms-of-service');
Route::get('/data-deletion', [\App\Http\Controllers\PublicLegalController::class, 'dataDeletion'])->name('data-deletion');
Route::get('/dashboard', \App\Http\Controllers\Marketplace\AccountDashboardController::class)->middleware('auth')->name('dashboard');
require __DIR__.'/marketplace.php';

Route::post('/support/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('support.store');
Route::get('/users/{user}/likes', [\App\Http\Controllers\ProfileLikeController::class, 'index'])->middleware('throttle:60,1');

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
        Route::patch('/users/{user}/restriction', [App\Http\Controllers\Admin\AccountRestrictionController::class, 'update'])->name('users.restriction');
    Route::patch('/users/{user}/deletion', [App\Http\Controllers\Admin\AccountDeletionController::class, 'update'])->name('users.deletion');
        Route::patch('/users/{user}/aadi-plus', [App\Http\Controllers\Admin\AadiPlusEntitlementController::class, 'update'])->name('users.aadi-plus');
        Route::get('/aadiplus', [App\Http\Controllers\Admin\AadiPlusController::class, 'index'])->name('aadiplus.index');
        Route::get('/pages', [App\Http\Controllers\Admin\PageManagementController::class, 'index'])->name('pages.index');
        Route::patch('/pages/{page}', [App\Http\Controllers\Admin\PageManagementController::class, 'update'])->name('pages.update');
        Route::patch('/aadiplus/perks/{perk}', [App\Http\Controllers\Admin\AadiPlusController::class, 'updatePerk'])->name('aadiplus.perks');
        Route::post('/aadiplus/members', [App\Http\Controllers\Admin\AadiPlusController::class, 'grant'])->name('aadiplus.grant');
        Route::post('/users/{user}/password-reset', [App\Http\Controllers\Admin\AccountRestrictionController::class, 'resetPassword'])->middleware('throttle:5,1')->name('users.password-reset');
        Route::get('/listings', [App\Http\Controllers\Admin\ListingInspectionController::class, 'spaces'])->name('listings.index');
        Route::get('/vehicles', [App\Http\Controllers\Admin\ListingInspectionController::class, 'vehicles'])->name('vehicles.index');
        Route::get('/services', [App\Http\Controllers\Admin\ListingInspectionController::class, 'services'])->name('services.index');
        Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/messages', [App\Http\Controllers\Admin\TicketController::class, 'message'])->middleware('throttle:60,1')->name('tickets.message');
        Route::get('/analytics', [App\Http\Controllers\Admin\DashboardController::class, 'analytics'])->name('analytics');
        Route::get('/reports', [App\Http\Controllers\Admin\AccountReportController::class, 'index'])->name('reports.index');
        Route::patch('/reports/{accountReport}', [App\Http\Controllers\Admin\AccountReportController::class, 'update'])->name('reports.update');
        
        // Redirect /admin to dashboard
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/users/{user}/reports', [\App\Http\Controllers\AccountReportController::class, 'store'])->middleware('throttle:5,1');
    Route::put('/users/{user}/likes', [\App\Http\Controllers\ProfileLikeController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/users/{user}/likes', [\App\Http\Controllers\ProfileLikeController::class, 'destroy'])->middleware('throttle:30,1');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'count'])->middleware('throttle:120,1');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->middleware('throttle:30,1');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->middleware('throttle:120,1');
    Route::post('/profile/avatar', [\App\Http\Controllers\AvatarController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('/profile/avatar', [\App\Http\Controllers\AvatarController::class, 'destroy']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:5,1,account-deletion')->name('profile.destroy');
});

Route::put('/account/onboarding', [\App\Http\Controllers\OnboardingController::class, 'update'])->middleware(['auth','throttle:20,1']);

require __DIR__.'/auth.php';
