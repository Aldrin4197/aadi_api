<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoritesController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\ServiceListingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleListingController;
use App\Http\Controllers\Api\ViewingRequestController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes (public)
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('me/landlord-application', [AuthController::class, 'applyForLandlord']);
    Route::post('me/landlord-application/approve', [AuthController::class, 'approveLandlord'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/vehicle-owner-application', [AuthController::class, 'applyForVehicleOwner']);
    Route::post('me/vehicle-owner-application/approve', [AuthController::class, 'approveVehicleOwner'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/vehicle-owner-application/approve/{userId}', [AuthController::class, 'approveVehicleOwner'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/service-provider-application', [AuthController::class, 'applyForServiceProvider']);
    Route::post('me/service-provider-application/approve', [AuthController::class, 'approveServiceProvider'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/service-provider-application/approve/{userId}', [AuthController::class, 'approveServiceProvider'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/aadi-haul-application', [AuthController::class, 'applyForVehicleOwner']);
    Route::post('me/aadi-haul-application/approve', [AuthController::class, 'approveVehicleOwner'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/aadi-haul-application/approve/{userId}', [AuthController::class, 'approveVehicleOwner'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/aadi-raketeer-application', [AuthController::class, 'applyForServiceProvider']);
    Route::post('me/aadi-raketeer-application/approve', [AuthController::class, 'approveServiceProvider'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('me/aadi-raketeer-application/approve/{userId}', [AuthController::class, 'approveServiceProvider'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    
    // Listings
    Route::get('listings', [ListingController::class, 'search']);
    Route::get('listings/{id}', [ListingController::class, 'show']);
    Route::get('listings/{id}/analytics', [ListingController::class, 'analytics']);
    Route::post('listings', [ListingController::class, 'store']);
    Route::put('listings/{id}', [ListingController::class, 'update']);
    Route::delete('listings/{id}', [ListingController::class, 'destroy']);
    
    // Users
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('users/{ownerId}/listings', [ListingController::class, 'listingsByOwner']);
    
    // Viewing Requests
    Route::get('me/viewings', [ViewingRequestController::class, 'index']);
    Route::post('me/viewings', [ViewingRequestController::class, 'store']);
    Route::put('me/viewings/{id}', [ViewingRequestController::class, 'update']);
    Route::delete('me/viewings/{id}', [ViewingRequestController::class, 'destroy']);
    
    // Favorites
    Route::get('me/favorites', [FavoritesController::class, 'index']);
    Route::put('me/favorites/{listingId}', [FavoritesController::class, 'store']);
    Route::delete('me/favorites/{listingId}', [FavoritesController::class, 'destroy']);
    
    // Saved Searches
    Route::get('me/saved-searches', [SavedSearchController::class, 'index']);
    Route::post('me/saved-searches', [SavedSearchController::class, 'store']);
    Route::delete('me/saved-searches/{id}', [SavedSearchController::class, 'destroy']);
    
    // Vehicle Listings
    Route::get('vehicles', [VehicleListingController::class, 'index']);
    Route::get('vehicles/{id}', [VehicleListingController::class, 'show']);
    Route::post('vehicles', [VehicleListingController::class, 'store']);
    Route::put('vehicles/{id}', [VehicleListingController::class, 'update']);
    Route::delete('vehicles/{id}', [VehicleListingController::class, 'destroy']);
    Route::get('haul', [VehicleListingController::class, 'index']);
    Route::get('haul/{id}', [VehicleListingController::class, 'show']);
    Route::post('haul', [VehicleListingController::class, 'store']);
    Route::put('haul/{id}', [VehicleListingController::class, 'update']);
    Route::delete('haul/{id}', [VehicleListingController::class, 'destroy']);
    
    // Service Listings
    Route::get('services', [ServiceListingController::class, 'index']);
    Route::get('services/{id}', [ServiceListingController::class, 'show']);
    Route::post('services', [ServiceListingController::class, 'store']);
    Route::put('services/{id}', [ServiceListingController::class, 'update']);
    Route::delete('services/{id}', [ServiceListingController::class, 'destroy']);
    Route::get('raketeer', [ServiceListingController::class, 'index']);
    Route::get('raketeer/{id}', [ServiceListingController::class, 'show']);
    Route::post('raketeer', [ServiceListingController::class, 'store']);
    Route::put('raketeer/{id}', [ServiceListingController::class, 'update']);
    Route::delete('raketeer/{id}', [ServiceListingController::class, 'destroy']);
});

// Admin API Routes
Route::prefix('admin')->middleware('web')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('web');
    Route::get('/me', [AdminAuthController::class, 'me'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
});

// Versioned marketplace contract; web and mobile call the same application service.
Route::prefix('v1')->group(function () {
    Route::get('/offers', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'search']);
    Route::get('/offers/{service}/{id}', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/offers/{service}', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'store']);
        Route::put('/offers/{service}/{id}', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'update']);
        Route::delete('/offers/{service}/{id}', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'destroy']);
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class,'show']);
        Route::patch('/profile', [\App\Http\Controllers\Api\ProfileController::class,'update']);
        Route::post('/profile/avatar', [\App\Http\Controllers\AvatarController::class,'store'])->middleware('throttle:10,1');
        Route::delete('/profile/avatar', [\App\Http\Controllers\AvatarController::class,'destroy']);
        Route::get('/provider-applications/{service}', [\App\Http\Controllers\Marketplace\ProviderApplicationController::class,'show']);
        Route::post('/provider-applications/{service}', [\App\Http\Controllers\Marketplace\ProviderApplicationController::class,'store'])->middleware('throttle:6,1');
        Route::get('/provider-documents/{id}/{index}', [\App\Http\Controllers\Marketplace\ProviderApplicationController::class,'document']);
        Route::post('/photos', [\App\Http\Controllers\Marketplace\MarketplaceController::class,'upload'])->middleware('throttle:30,1');
    });
});

// Saved listings and reviews share the web/mobile contract.
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/saved-listings', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'saved']);
    Route::get('/offers/{service}/{id}/engagement', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'show']);
    Route::put('/offers/{service}/{id}/save', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'save'])->middleware('throttle:30,1');
    Route::delete('/offers/{service}/{id}/save', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'unsave'])->middleware('throttle:30,1');
    Route::put('/offers/{service}/{id}/review', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'review'])->middleware('throttle:30,1');
    Route::delete('/offers/{service}/{id}/review', [\App\Http\Controllers\Marketplace\ListingEngagementController::class, 'deleteReview'])->middleware('throttle:30,1');
});
