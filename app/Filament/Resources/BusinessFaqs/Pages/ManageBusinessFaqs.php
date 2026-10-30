<?php

namespace App\Filament\Resources\BusinessFaqs\Pages;

use App\Filament\Resources\BusinessFaqs\BusinessFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBusinessFaqs extends ManageRecords
{
    protected static string $resource = BusinessFaqResource::class;

    public function getSubheading(): ?string
    {
        return 'Internal FAQ library. Google discontinued its Business Profile Q&A API, so these entries do not sync to the Google profile.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
