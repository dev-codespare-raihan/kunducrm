<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

class WebsiteSettingsService
{
    private const KEY = 'website_settings';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'site_name' => 'Kundu Tour and Travels',
            'tagline' => 'Doorstep to destination',
            'seo_title' => 'Car Rental in South Kolkata | Kundu Tour and Travels - Garia, Sonarpur 24/7',
            'seo_description' => 'Car rental in South Kolkata with verified drivers. Garia, Sonarpur, Kamdahari and Rajpur. Call +91 89812 53570. Local, outstation and airport. No surge pricing.',
            'seo_keywords' => 'car rental Kolkata, Garia car rental, airport transfer',
            'robots_index' => true,
            'cars_page' => [
                'eyebrow' => 'Fleet · maintained locally',
                'title' => 'Innova Crysta on rent in Kolkata, and the cars beside it',
                'intro' => 'Four cars cover almost every call from Garia, Sonarpur, Kamdahari and Rajpur. Swift Dzire for the city and the airport. Ertiga for the family. Innova Crysta for outstation and weddings. Tempo Traveller when the group does not fit in one cabin.',
                'details' => 'Swift Dzire on rent in Garia is the booking we do most: ₹1,800 for 8 hours and 80 km. Innova Crysta on rent in Kolkata starts at ₹3,200 for the same city package, and moves to a per-kilometre fare from ₹12/km once you leave for Digha, Puri or Darjeeling. All of them include a driver. None of them are self-drive.',
                'special_title' => 'Wedding and monthly use',
                'special_text' => 'Wedding packages start at ₹3,500 for a decorated car and guest drops. A ₹1,000 token holds the date and comes back if you cancel 24 hours before. Monthly car rental in Kolkata — a dedicated car and driver for New Garia, Patuli or Narendrapur — is quoted from the days and kilometres you actually need, with a GST invoice. Ask on the phone rather than trust a printed monthly figure.',
                'fleet_title' => 'The four cars',
                'pricing_title' => 'City package prices',
                'price_notes' => [
                    'Local package is 8 hours and 80 km, driver included. A 12 hour / 120 km city day is quoted on the call.',
                    'Extra kilometres are ₹12 to ₹18, depending on the car.',
                    'Outstation car rental from Kolkata starts at ₹12/km and is charged both ways, because the driver returns.',
                    'Airport pickup and drop from South Kolkata starts at ₹1,500 in a Swift Dzire.',
                    'Outstation driver allowance is ₹300 a day. Toll, parking and state permit are extra at actual cost.',
                    'No night charge for local trips inside Kolkata. No advance for South Kolkata city or airport runs.',
                    'Wedding car packages from ₹3,500. Outstation and wedding bookings take a ₹1,000 token, refunded if you cancel 24 hours before.',
                ],
                'seo_title' => 'Our Cars | Swift Dzire, Innova Crysta on Rent in Kolkata',
                'seo_description' => 'Swift Dzire on rent in Garia from ₹1,800. Innova Crysta on rent in Kolkata from ₹3,200. Ertiga and Tempo Traveller. Call +91 89812 53570.',
            ],
            'tours_page' => [
                'eyebrow' => 'Packages · driver included',
                'title' => 'Tour packages from South Kolkata',
                'intro' => 'City days use the published 8 hour package. Outstation is ₹12/km both ways, plus ₹300 a day for the driver and tolls at the receipt. Nothing on this page is a surprise add-on. Call +91 89812 53570 and we read the split before the car is blocked.',
                'details' => 'These are car-and-driver packages, not hotel bookings. You choose where to stay. We drive, wait, and bring you back.',
                'outstation_link_label' => 'How the outstation kilometre is counted',
                'seo_title' => 'Tour Packages | Kolkata Day Trips, Digha, Puri, Darjeeling',
                'seo_description' => 'Kolkata heritage, temple circuit, Digha, Mandarmani, Puri and Darjeeling by car from South Kolkata. From ₹1,800 in the city, ₹12/km outstation. Call +91 89812 53570.',
            ],
            'logo_path' => null,
            'favicon_path' => null,
            'colors' => [
                'navy' => '#0f172a', 'navy_deep' => '#0b1222', 'ink' => '#0f172a',
                'muted' => '#475569', 'faint' => '#64748b', 'line' => '#e2e8f0',
                'paper' => '#fffdf8', 'cream' => '#fff7e8', 'saffron' => '#f59e0b',
                'saffron_deep' => '#d97706', 'saffron_ink' => '#92400e', 'surface' => '#ffffff',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        $saved = AppSetting::query()->where('key', self::KEY)->first()?->value;
        $settings = is_string($saved) ? json_decode($saved, true) : null;
        $settings = is_array($settings) ? array_replace_recursive($this->defaults(), $settings) : $this->defaults();
        $settings['logo_url'] = $this->publicUrl($settings['logo_path'] ?? null);
        $settings['favicon_url'] = $this->publicUrl($settings['favicon_path'] ?? null);

        return $settings;
    }

    /** @param array<string, mixed> $settings */
    public function save(array $settings): void
    {
        $existing = $this->get();
        $settings = array_replace_recursive($existing, $settings);
        unset($settings['logo_url'], $settings['favicon_url']);

        AppSetting::query()->updateOrCreate(['key' => self::KEY], ['value' => json_encode($settings, JSON_THROW_ON_ERROR)]);
    }

    private function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        return str_starts_with($url, '/') ? url($url) : $url;
    }
}
