<?php

namespace App\Filament\Resources\GoogleMedia\Pages;

use App\Filament\Resources\GoogleMedia\GoogleMediaResource;
use App\Services\GoogleBusinessProfileService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ManageGoogleMedia extends ManageRecords
{
    protected static string $resource = GoogleMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncMedia')->label('Sync from Google')->icon(Heroicon::OutlinedArrowPath)->action(function (GoogleBusinessProfileService $google): void {
                try {
                    $google->syncMedia();
                    Notification::make()->success()->title('Google photos synced')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Photo sync failed')->body($exception->getMessage())->send();
                }
            }),
            CreateAction::make(),
        ];
    }
}
