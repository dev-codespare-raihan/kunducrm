<?php

namespace App\Filament\Pages;

use App\Models\GoogleBusinessConnection;
use App\Services\GoogleBusinessCredentials;
use App\Services\GoogleBusinessProfileService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class IntegrationSettings extends Page
{
    protected string $view = 'filament.pages.integration-settings';

    protected static ?string $slug = 'integrations';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $navigationLabel = 'Integrations';

    public string $callbackUri = '';

    public bool $credentialsConfigured = false;

    public bool $clientIdConfigured = false;

    public bool $clientSecretConfigured = false;

    public bool $googleConnected = false;

    public bool $profileSelected = false;

    public ?string $connectedEmail = null;

    public ?string $discoveryError = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(GoogleBusinessCredentials $credentials): void
    {
        $this->callbackUri = $credentials->redirectUri() ?? route('google-business.callback');
        $this->refreshCredentialStatus($credentials);
        $connection = GoogleBusinessConnection::query()->first();
        $this->googleConnected = (bool) $connection;
        $this->connectedEmail = $connection?->connected_email;
        $this->profileSelected = filled($connection?->location_resource);
        $this->discoveryError = $connection?->discovery_error;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureGoogle')
                ->label('Configure Google credentials')
                ->icon(Heroicon::OutlinedKey)
                ->color('primary')
                ->modalHeading('Google Business Profile credentials')
                ->modalDescription('The client secret is stored encrypted. Leave it blank to keep the saved value.')
                ->schema([
                    TextInput::make('client_id')
                        ->label('OAuth Client ID')
                        ->required()
                        ->maxLength(255)
                        ->default(fn (): ?string => app(GoogleBusinessCredentials::class)->clientId()),
                    TextInput::make('client_secret')
                        ->label('OAuth Client Secret')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->maxLength(2000)
                        ->placeholder('Paste the client secret; blank keeps the saved value'),
                    TextInput::make('redirect_uri')
                        ->label('Authorized redirect URI')
                        ->url()
                        ->required()
                        ->maxLength(2048)
                        ->helperText('This must exactly match the URI registered on your Google OAuth web client.')
                        ->default(fn (): string => app(GoogleBusinessCredentials::class)->redirectUri() ?? route('google-business.callback')),
                ])
                ->action(function (array $data, GoogleBusinessCredentials $credentials): void {
                    try {
                        $credentials->save($data);
                        $this->callbackUri = $credentials->redirectUri() ?? route('google-business.callback');
                        $this->refreshCredentialStatus($credentials);
                        Notification::make()
                            ->success()
                            ->title($this->credentialsConfigured ? 'Google credentials saved' : 'Google settings saved')
                            ->body($this->credentialsConfigured ? 'You can now connect the Business Profile account.' : 'Add the OAuth client secret before connecting Google.')
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->danger()
                            ->title('Google credentials could not be saved')
                            ->body('Check the OAuth Client ID, client secret, and redirect URI, then try again.')
                            ->send();
                    }
                }),
            Action::make('connectGoogle')
                ->label('Connect with Google')
                ->icon(Heroicon::OutlinedLink)
                ->color('success')
                ->url(route('google-business.connect'))
                ->visible(fn (): bool => $this->credentialsConfigured && ! $this->googleConnected),
            Action::make('retryProfileDiscovery')
                ->label('Retry profile discovery')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->action(function (GoogleBusinessProfileService $google): void {
                    try {
                        $discovery = $google->discoverBusinessProfiles();
                        $connection = $google->connection();
                        $this->profileSelected = filled($connection?->location_resource);
                        $this->discoveryError = $connection?->discovery_error;

                        if ($discovery['selected']) {
                            Notification::make()
                                ->success()
                                ->title('Google profile discovered and imported')
                                ->body('The business profile details are now available in Google Business Profile → Business Profile.')
                                ->send();
                        } elseif ($discovery['locations'] !== []) {
                            Notification::make()
                                ->warning()
                                ->title('Choose the business location')
                                ->body('Google returned '.count($discovery['locations']).' profiles. Open Google Business Profile → Business Profile to select Kundu Tour and Travels.')
                                ->send();
                        } else {
                            Notification::make()->warning()->title('No Google Business Profile locations found')->body($this->discoveryError ?? 'Check that this account manages a profile.')->send();
                        }
                    } catch (Throwable $exception) {
                        $this->discoveryError = $google->connection()?->discovery_error ?? $google->userFacingGoogleError($exception);
                        Notification::make()
                            ->danger()
                            ->title('Google profile discovery failed')
                            ->body($this->discoveryError)
                            ->send();
                    }
                })
                ->visible(fn (): bool => $this->googleConnected && ! $this->profileSelected),
        ];
    }

    private function refreshCredentialStatus(GoogleBusinessCredentials $credentials): void
    {
        $this->clientIdConfigured = filled($credentials->clientId());
        $this->clientSecretConfigured = filled($credentials->clientSecret());
        $this->credentialsConfigured = $credentials->isConfigured();
    }
}
