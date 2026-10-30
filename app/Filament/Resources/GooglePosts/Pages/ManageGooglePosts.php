<?php

namespace App\Filament\Resources\GooglePosts\Pages;

use App\Filament\Resources\GooglePosts\GooglePostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGooglePosts extends ManageRecords
{
    protected static string $resource = GooglePostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
