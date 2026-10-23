<?php
use App\Http\Controllers\Marketplace\MarketplaceController as Market;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Controllers\Marketplace\ProviderApplicationController as Application;
use Illuminate\Support\Facades\Route;
Route::get('/explore', [Market::class,'index'])->middleware(\App\Http\Middleware\PrivateMarketplaceResponse::class);
Route::get('/offers/{service}/{id}', [Market::class,'show'])->middleware(\App\Http\Middleware\PrivateMarketplaceResponse::class);
Route::get('/offers/{service}/{id}/unlock', [Market::class,'unlock'])->middleware(\App\Http\Middleware\PrivateMarketplaceResponse::class);
Route::middleware(['auth', \App\Http\Middleware\PrivateMarketplaceResponse::class])->group(function () {
    Route::get('/inquiries', [\App\Http\Controllers\Marketplace\InquiryController::class, 'index']);
    Route::get('/inquiries/{id}', [\App\Http\Controllers\Marketplace\InquiryController::class, 'show']);
    Route::post('/offers/{service}/{id}/inquiries', [\App\Http\Controllers\Marketplace\InquiryController::class, 'store'])->middleware('throttle:6,1');
    Route::post('/inquiries/{id}/messages', [\App\Http\Controllers\Marketplace\InquiryController::class, 'reply'])->middleware('throttle:20,1');
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
