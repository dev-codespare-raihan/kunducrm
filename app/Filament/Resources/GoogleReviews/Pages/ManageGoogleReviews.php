<?php

namespace App\Filament\Resources\GoogleReviews\Pages;

use App\Filament\Resources\GoogleReviews\GoogleReviewResource;
use App\Services\GoogleBusinessProfileService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ManageGoogleReviews extends ManageRecords
{
    protected static string $resource = GoogleReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncReviews')->label('Sync from Google')->icon(Heroicon::OutlinedArrowPath)->action(function (GoogleBusinessProfileService $google): void {
                try {
                    $google->syncReviews();
                    Notification::make()->success()->title('Reviews synced')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Review sync failed')->body($exception->getMessage())->send();
                }
            })->visible(fn (): bool => auth()->user()?->canManageGoogle() ?? false),
        ];
    }
}
