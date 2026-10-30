<?php

namespace App\Filament\Widgets;

use App\Services\GoogleBusinessProfileService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Throwable;

class GoogleBusinessInsights extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            $this->metric('Calls from Google', 'calls', 'heroicon-m-phone'),
            $this->metric('Direction requests', 'directions', 'heroicon-m-map-pin'),
            $this->metric('Website clicks', 'website', 'heroicon-m-cursor-arrow-rays'),
            $this->metric('Profile views', 'views', 'heroicon-m-eye'),
        ];
    }

    private function metric(string $label, string $key, string $icon): Stat
    {
        try {
            $totals = app(GoogleBusinessProfileService::class)->performanceTotals();
        } catch (Throwable) {
            $totals = null;
        }

        return Stat::make($label, $totals[$key] ?? '—')
            ->description($totals ? 'Last 28 days' : 'Connect Google in Business Profile settings')
            ->descriptionIcon($icon)
            ->color($totals ? 'success' : 'gray');
    }
}
