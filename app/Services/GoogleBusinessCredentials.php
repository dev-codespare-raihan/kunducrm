<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Validator;

class GoogleBusinessCredentials
{
    public function clientId(): ?string
    {
        return $this->configuredValue('google_business_client_id', 'services.google_business.client_id');
    }

    public function clientSecret(): ?string
    {
        return $this->configuredValue('google_business_client_secret', 'services.google_business.client_secret');
    }

    public function redirectUri(): ?string
    {
        return $this->configuredValue('google_business_redirect_uri', 'services.google_business.redirect_uri');
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret()) && filled($this->redirectUri());
    }

    /**
     * @param  array{client_id: string, client_secret?: string|null, redirect_uri: string}  $data
     */
    public function save(array $data): void
    {
        Validator::make($data, [
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
        ])->validate();

        $this->store('google_business_client_id', $data['client_id']);
        $this->store('google_business_redirect_uri', $data['redirect_uri']);

        if (filled($data['client_secret'] ?? null)) {
            $this->store('google_business_client_secret', $data['client_secret']);
        }
    }

    private function configuredValue(string $key, string $configKey): ?string
    {
        $value = AppSetting::query()->where('key', $key)->first()?->value;

        return filled($value) ? $value : (filled(config($configKey)) ? config($configKey) : null);
    }

    private function store(string $key, string $value): void
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
