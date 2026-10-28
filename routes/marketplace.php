<?php
use App\Http\Controllers\Marketplace\MarketplaceController as Market;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Controllers\Marketplace\ProviderApplicationController as Application;
use Illuminate\Support\Facades\Route;
Route::get('/explore', [Market::class,'index']);
Route::get('/offers/{service}/{id}', [Market::class,'show'])->middleware('auth');
Route::middleware('auth')->group(function () {
    Route::get('/my-listings', [Market::class,'manage']);
    Route::get('/my-listings/{service}/new', [Market::class,'form']);
    Route::get('/my-listings/{service}/{id}/edit', [Market::class,'form']);
    Route::post('/my-listings/{service}', [Market::class,'store']);
    Route::put('/my-listings/{service}/{id}', [Market::class,'update']);
    Route::delete('/my-listings/{service}/{id}', [Market::class,'destroy']);
    Route::post('/listing-photos', [Market::class,'upload'])->middleware('throttle:30,1');
    Route::get('/become-provider', [Application::class,'index']);
    Route::get('/provider-applications/{service}', [Application::class,'show']);
    Route::get('/provider-documents/{id}/{index}', [Application::class,'document']);
    Route::post('/provider-applications/{service}', [Application::class,'store'])->middleware('throttle:6,1');
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

Route::get('/providers/{user}', \App\Http\Controllers\Marketplace\ProviderProfileController::class);
Route::get('/avatars/{user}/{filename}', [\App\Http\Controllers\AvatarController::class, 'show'])->name('avatars.show');
