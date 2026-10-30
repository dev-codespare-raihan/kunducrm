<?php

namespace App\Filament\Resources\BusinessProfiles\Pages;

use App\Filament\Resources\BusinessProfiles\BusinessProfileResource;
use App\Models\GoogleBusinessConnection;
use App\Services\GoogleBusinessProfileService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ManageBusinessProfiles extends ManageRecords
{
    protected static string $resource = BusinessProfileResource::class;

    public function getSubheading(): ?string
    {
        $connection = GoogleBusinessConnection::query()->first();

        if (! $connection) {
            return 'Connect the Google account that manages your Business Profile to publish profile changes and manage reviews, posts, photos, and insights.';
        }

        if (blank($connection->location_resource)) {
            return 'Google account '.$connection->connected_email.' is connected, but no Business Profile location is selected. Retry discovery in Settings → Integrations or select a location here.';
        }

        return 'Connected to '.$connection->connected_email.'. Profile details were imported from Google. Edit here and publish changes to Google when ready.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectGoogle')->label('Connect with Google')->icon(Heroicon::OutlinedLink)->url(route('google-business.connect'))->visible(fn (): bool => auth()->user()?->isAdmin() && ! GoogleBusinessConnection::query()->exists()),
            Action::make('chooseLocation')->label('Choose Google profile')->icon(Heroicon::OutlinedMapPin)->mountUsing(function (Action $action, GoogleBusinessProfileService $google): void {
                try {
                    $locations = $google->listLocations();
                    $this->profileLocationOptions = collect($locations)->mapWithKeys(function (array $location): array {
                        $address = collect($location['storefrontAddress']['addressLines'] ?? [])->implode(', ');
                        $label = collect([$location['title'] ?? 'Untitled location', $address, $location['accountName'] ?? null])->filter()->implode(' — ');

                        return [($location['accountResource'] ?? '').'|'.($location['name'] ?? '') => $label];
                    })->all();

                    if ($this->profileLocationOptions === []) {
                        Notification::make()->warning()->title('No Google Business Profile locations found')->body('Check profile access and API setup in Settings → Integrations.')->send();
                        $action->halt();
                    }
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Google profiles could not be loaded')->body($google->userFacingGoogleError($exception))->send();
                    $action->halt();
                }
            })->schema([
                Select::make('google_location')->label('Business location')->options(fn (): array => $this->profileLocationOptions)->searchable()->required(),
            ])->action(function (array $data, GoogleBusinessProfileService $google): void {
                [$accountResource, $locationResource] = array_pad(explode('|', $data['google_location'], 2), 2, '');

                try {
                    $google->selectLocation($accountResource, $locationResource);
                    Notification::make()->success()->title('Google Business Profile selected')->body('Its business details were imported into the CRM.')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Google Business Profile could not be selected')->body($google->userFacingGoogleError($exception))->send();
                }
            })->visible(fn (): bool => auth()->user()?->isAdmin() && GoogleBusinessConnection::query()->exists()),
            Action::make('disconnectGoogle')->label('Disconnect Google')->color('danger')->icon(Heroicon::OutlinedLinkSlash)->requiresConfirmation()->action(function (GoogleBusinessProfileService $google): void {
                $google->disconnect();
                Notification::make()->success()->title('Google account disconnected')->send();
            })->visible(fn (): bool => auth()->user()?->isAdmin() && GoogleBusinessConnection::query()->exists()),
        ];
    }

    /** @var array<string, string> */
    public array $profileLocationOptions = [];
}
