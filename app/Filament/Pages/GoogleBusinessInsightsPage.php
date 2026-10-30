<?php

namespace App\Filament\Pages;

use App\Models\GoogleBusinessConnection;
use App\Services\GoogleBusinessProfileService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class GoogleBusinessInsightsPage extends Page
{
    protected string $view = 'filament.pages.google-business-insights-page';

    protected static string|\UnitEnum|null $navigationGroup = 'Google Business Profile';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Insights';

    /** @var array{calls: int, directions: int, website: int, views: int}|null */
    public ?array $insights = null;

    public ?string $loadError = null;

    public function mount(GoogleBusinessProfileService $google): void
    {
        $this->refreshInsights($google);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectGoogle')->label('Connect with Google')->icon(Heroicon::OutlinedLink)->url(route('google-business.connect'))->visible(fn (): bool => auth()->user()?->isAdmin() && ! GoogleBusinessConnection::query()->exists()),
            Action::make('refresh')->label('Refresh insights')->icon(Heroicon::OutlinedArrowPath)->action(fn (GoogleBusinessProfileService $google) => $this->refreshInsights($google))->visible(fn (): bool => GoogleBusinessConnection::query()->exists()),
        ];
    }

    private function refreshInsights(GoogleBusinessProfileService $google): void
    {
        try {
            $this->insights = $google->performanceTotals();
            $this->loadError = $this->insights ? null : 'Connect a Google Business Profile to see analytics.';
            if ($this->insights) {
                Notification::make()->success()->title('Google insights refreshed')->send();
            }
        } catch (Throwable $exception) {
            $this->insights = null;
            $this->loadError = $exception->getMessage();
        }
    }
}
