<?php

namespace App\Http\Controllers;

use App\Services\GoogleBusinessProfileService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GoogleBusinessOAuthController extends Controller
{
    public function connect(Request $request, GoogleBusinessProfileService $google): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $state = Str::random(48);
        $request->session()->put('google_business_oauth_state', $state);

        try {
            return redirect()->away($google->authorizationUrl($state));
        } catch (Throwable $exception) {
            Notification::make()->danger()->title('Google connection is not configured')->body($exception->getMessage())->send();

            return redirect()->route('filament.admin.resources.business-profiles.index');
        }
    }

    public function callback(Request $request, GoogleBusinessProfileService $google): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $expectedState = $request->session()->pull('google_business_oauth_state');
        abort_unless(is_string($expectedState) && hash_equals($expectedState, (string) $request->query('state')), 403);

        if ($request->filled('error')) {
            Notification::make()->warning()->title('Google account connection was cancelled')->send();

            return redirect()->route('filament.admin.resources.business-profiles.index');
        }

        try {
            $discovery = $google->connect((string) $request->query('code'));

            if ($discovery['selected']) {
                Notification::make()
                    ->success()
                    ->title('Google Business Profile connected')
                    ->body('Your only accessible profile was selected and its business details were imported into the CRM.')
                    ->send();
            } elseif ($discovery['locations'] !== []) {
                Notification::make()
                    ->success()
                    ->title('Google account connected')
                    ->body('Google returned '.count($discovery['locations']).' profiles. Choose the Kundu Tour and Travels location to finish setup.')
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('Google account connected, but no profile was found')
                    ->body($google->connection()?->discovery_error ?? 'Confirm this Google account manages a Business Profile, then retry discovery.')
                    ->send();
            }

            return redirect()->route('filament.admin.resources.business-profiles.index');
        } catch (Throwable $exception) {
            $message = $google->userFacingGoogleError($exception);
            Log::warning('Google Business Profile OAuth or discovery failed.', [
                'user_id' => $request->user()?->id,
                'message' => $message,
            ]);
            Notification::make()
                ->danger()
                ->title($google->connection() ? 'Google account connected, but profile discovery failed' : 'Google could not be connected')
                ->body($message)
                ->send();

            return redirect()->route('filament.admin.pages.integrations');
        }
    }

    public function disconnect(Request $request, GoogleBusinessProfileService $google): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $google->disconnect();

        return redirect()->route('filament.admin.resources.business-profiles.index')->with('success', 'Google Business Profile disconnected.');
    }
}
