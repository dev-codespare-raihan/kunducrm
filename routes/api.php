<?php

use App\Http\Controllers\Api\V1\WebsiteContentController;
use App\Http\Controllers\Api\V1\WebsiteLeadController;
use App\Http\Controllers\Api\V1\WebsiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/website-settings', WebsiteSettingsController::class)->name('api.v1.website-settings');
Route::get('/v1/website-content', WebsiteContentController::class)->name('api.v1.website-content');
Route::post('/v1/website-leads', WebsiteLeadController::class)->middleware('throttle:5,1')->name('api.v1.website-leads.store');
