<?php

use App\Models\GoogleBusinessConnection;
use App\Models\GoogleMedia;
use App\Models\GoogleReview;
use App\Services\GoogleBusinessProfileService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    if (GoogleBusinessConnection::query()->whereNotNull('location_resource')->exists()) {
        try {
            $google = app(GoogleBusinessProfileService::class);
            $google->syncReviews();
            $google->syncMedia();
            $google->performanceTotals();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    GoogleReview::query()->where('created_at', '<', now()->subDays(29))->delete();
    GoogleMedia::query()->where('created_at', '<', now()->subDays(29))->whereNull('local_path')->delete();
})->dailyAt('03:00')->name('google-business-profile-sync')->withoutOverlapping();
