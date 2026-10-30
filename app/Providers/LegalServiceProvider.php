<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalController;
class LegalServiceProvider extends ServiceProvider
{
    public function boot(): void {
        Route::get('/legal/terms',[LegalController::class,'show'])->middleware('web');
        Route::middleware(['web','auth'])->group(function () {
            Route::get('/legal/accept',[LegalController::class,'show'])->name('legal.accept');
            Route::post('/legal/accept',[LegalController::class,'store'])->middleware('throttle:20,1');
        });
    }
}
