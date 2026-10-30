<?php

namespace App\Filament\Widgets;

use App\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentalDashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('New inquiries', Lead::query()->where('status', LeadStatus::New)->count())
                ->description('Waiting for a first response')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('warning'),
            Stat::make('Total leads', Lead::query()->count())
                ->description('All customer inquiries')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('Customers', Customer::query()->count())
                ->description('Converted and existing customers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
        ];
    }
}
