<?php
use App\Http\Controllers\Marketplace\MarketplaceController as Market;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
Route::get('/explore', [Market::class,'index']);
Route::get('/offers/{service}/{id}', [Market::class,'show']);
Route::middleware('auth')->group(function () {
    Route::get('/my-listings', [Market::class,'manage']);
    Route::get('/my-listings/{service}/new', [Market::class,'form']);
    Route::get('/my-listings/{service}/{id}/edit', [Market::class,'form']);
    Route::post('/my-listings/{service}', [Market::class,'store']);
    Route::put('/my-listings/{service}/{id}', [Market::class,'update']);
    Route::delete('/my-listings/{service}/{id}', [Market::class,'destroy']);
    Route::post('/listing-photos', [Market::class,'upload'])->middleware('throttle:30,1');
    Route::post('/provider-applications/{service}', [Market::class,'apply'])->middleware('throttle:6,1');
    Route::get('/admin/approvals', [Market::class,'approvals'])->middleware(EnsureAdmin::class);
    Route::post('/admin/approvals/{id}/{service}', [Market::class,'decide'])->middleware(EnsureAdmin::class);
});
