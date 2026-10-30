<?php

use App\Http\Controllers\GoogleBusinessOAuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->prefix('integrations/google-business')->name('google-business.')->group(function (): void {
    Route::get('/connect', [GoogleBusinessOAuthController::class, 'connect'])->name('connect');
    Route::get('/callback', [GoogleBusinessOAuthController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [GoogleBusinessOAuthController::class, 'disconnect'])->name('disconnect');
});
