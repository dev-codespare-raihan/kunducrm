<?php

namespace App\Filament\Resources\WebsiteCars\Pages;

use App\Filament\Resources\WebsiteCars\WebsiteCarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWebsiteCars extends ManageRecords
{
    protected static string $resource = WebsiteCarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
