<?php
use App\Http\Controllers\Marketplace\MarketplaceController as Market;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Controllers\Marketplace\ProviderApplicationController as Application;
use Illuminate\Support\Facades\Route;
Route::get('/explore', [Market::class,'index'])->middleware('page.available:explore')->name('map');
Route::post('/offers/{service}/{id}/directions', \App\Http\Controllers\Marketplace\ListingDirectionsController::class)->middleware('throttle:30,1');
Route::get('/offers/{service}/{id}', [Market::class,'show'])->middleware('auth');
Route::middleware('auth')->group(function () {
    Route::middleware(\App\Http\Middleware\EnsureApprovedProvider::class)->prefix('provider/records')->group(function () {
        Route::get('/', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'index']);
        Route::post('/tenancies', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'createTenancy'])->middleware('throttle:30,1');
        Route::post('/{record}/tenancy', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'convert']);
        Route::patch('/{record}', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'update']);
        Route::post('/{record}/payments', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'payment']);
        Route::patch('/{record}/payments/{payment}/void', [\App\Http\Controllers\Marketplace\ProviderCrmController::class, 'voidPayment']);
    });
    Route::get('/messages', [\App\Http\Controllers\Marketplace\ListingConversationController::class, 'index']);
    Route::get('/messages/{conversation}', [\App\Http\Controllers\Marketplace\ListingConversationController::class, 'show']);
    Route::post('/messages/{conversation}', [\App\Http\Controllers\Marketplace\ListingConversationController::class, 'reply'])->middleware('throttle:30,1');
    Route::post('/offers/{service}/{id}/messages', [\App\Http\Controllers\Marketplace\ListingConversationController::class, 'start'])->middleware('throttle:30,1');
    Route::get('/requests', [\App\Http\Controllers\Marketplace\MarketplaceRequestController::class, 'index']);
    Route::get('/calendar', [\App\Http\Controllers\Marketplace\CalendarController::class, 'index']);
    Route::post('/calendar/events', [\App\Http\Controllers\Marketplace\CalendarController::class, 'store'])->middleware('throttle:30,1');
    Route::patch('/calendar/events/{calendarEvent}', [\App\Http\Controllers\Marketplace\CalendarController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/calendar/events/{calendarEvent}', [\App\Http\Controllers\Marketplace\CalendarController::class, 'destroy'])->middleware('throttle:30,1');
    Route::post('/offers/{service}/{id}/requests', [\App\Http\Controllers\Marketplace\MarketplaceRequestController::class, 'store'])->middleware('throttle:10,1');
    Route::patch('/requests/{marketplaceRequest}', [\App\Http\Controllers\Marketplace\MarketplaceRequestController::class, 'update'])->middleware('throttle:30,1');
    Route::get('/my-listings', [Market::class,'manage']);
    Route::get('/my-listings/{service}/new', [Market::class,'form']);
    Route::get('/my-listings/{service}/{id}/edit', [Market::class,'form']);
    Route::post('/my-listings/{service}', [Market::class,'store']);
    Route::put('/my-listings/{service}/{id}', [Market::class,'update']);
    Route::patch('/my-listings/{service}/{id}/availability', [Market::class,'availability']);
    Route::delete('/my-listings/{service}/{id}', [Market::class,'destroy']);
    Route::post('/listing-photos', [Market::class,'upload'])->middleware('throttle:30,1');
    Route::get('/become-provider', [Application::class,'index']);
    Route::get('/provider-applications/{service}', [Application::class,'show']);
    Route::get('/provider-documents/{id}/{index}', [Application::class,'document']);
    Route::post('/provider-applications/{service}', [Application::class,'store'])->middleware(['verified.email','throttle:6,1']);
    Route::get('/admin/approvals', [Application::class,'queue'])->middleware(EnsureAdmin::class);
    Route::post('/admin/approvals/{id}/{service}', [Application::class,'decide'])->middleware(EnsureAdmin::class);
});

// Saved listings and reviews share the web/mobile contract.
Route::middleware('auth')->group(function () {
    Route::get('/saved-listings', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'saved']);
    Route::put('/offers/{service}/{id}/save', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'save'])->middleware('throttle:30,1');
    Route::delete('/offers/{service}/{id}/save', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'unsave'])->middleware('throttle:30,1');
    Route::put('/offers/{service}/{id}/review', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'review'])->middleware('throttle:30,1');
    Route::delete('/offers/{service}/{id}/review', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'deleteReview'])->middleware('throttle:30,1');
});

Route::get('/providers/{user}', \App\Http\Controllers\Marketplace\ProviderProfileController::class)->middleware('page.available:providers');
Route::get('/avatars/{user}/{filename}', [\App\Http\Controllers\AvatarController::class, 'show'])->name('avatars.show');

Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::get('/admin/users/{user}/listings', [\App\Http\Controllers\Admin\UserListingsController::class, 'show']);
});

Route::get('/media/listing-photos/{owner}/{filename}', [\App\Http\Controllers\ListingPhotoController::class, 'show'])->where('owner', '[A-Za-z0-9]{26}')->where('filename', '[A-Za-z0-9_-]+\.(?i:jpg|jpeg|png|webp)')->name('listing-photos.show');
