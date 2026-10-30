<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\GoogleBusinessConnection;
use App\Models\GoogleMedia;
use App\Models\GooglePost;
use App\Models\GoogleReview;
use DateTimeImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleBusinessProfileService
{
    public function __construct(private GoogleBusinessCredentials $credentials) {}

    public function isConfigured(): bool
    {
        return $this->credentials->isConfigured();
    }

    public function authorizationUrl(string $state): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google OAuth credentials are not configured.');
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->credentials->clientId(),
            'redirect_uri' => $this->credentials->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email https://www.googleapis.com/auth/business.manage',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** @return array{email: string|null, locations: array<int, array<string, mixed>>, selected: bool} */
    public function connect(string $code): array
    {
        $tokens = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->credentials->clientId(),
            'client_secret' => $this->credentials->clientSecret(),
            'redirect_uri' => $this->credentials->redirectUri(),
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        $email = Http::withToken($tokens['access_token'])->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json('email');
        $connection = $this->connection() ?? new GoogleBusinessConnection;
        $isSameGoogleAccount = filled($connection->connected_email)
            && Str::lower($connection->connected_email) === Str::lower((string) $email);
        $connection->fill([
            'connected_email' => $email,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? ($isSameGoogleAccount ? $connection->refresh_token : null),
            'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
            'account_resource' => $isSameGoogleAccount ? $connection->account_resource : null,
            'location_resource' => $isSameGoogleAccount ? $connection->location_resource : null,
            'connected_at' => now(),
            'discovery_error' => null,
        ])->save();

        $discovery = $this->discoverBusinessProfiles();

        return ['email' => $email, ...$discovery];
    }

    public function connection(): ?GoogleBusinessConnection
    {
        return GoogleBusinessConnection::query()->first();
    }

    public function disconnect(): void
    {
        GoogleBusinessConnection::query()->delete();
        GoogleReview::query()->delete();
        GoogleMedia::query()->whereNull('local_path')->delete();
    }

    /** @return array{locations: array<int, array<string, mixed>>, selected: bool} */
    public function discoverBusinessProfiles(): array
    {
        $connection = $this->connection() ?? throw new RuntimeException('Connect a Google account first.');

        try {
            $locations = $this->listLocations();
        } catch (Throwable $exception) {
            $connection->update(['discovery_error' => $this->userFacingGoogleError($exception)]);

            throw $exception;
        }

        if ($locations === []) {
            $connection->update([
                'account_resource' => null,
                'location_resource' => null,
                'discovery_error' => 'Google connected successfully, but this account returned no Business Profile locations. Confirm the signed-in Google account is an owner or manager of the verified profile and that Business Profile API access is approved.',
            ]);

            return ['locations' => [], 'selected' => false];
        }

        if (count($locations) === 1) {
            $this->saveSelectedLocation($locations[0]);

            return ['locations' => $locations, 'selected' => true];
        }

        $accounts = collect($locations)->pluck('accountResource')->unique()->values();
        $connection->update([
            'account_resource' => $accounts->count() === 1 ? $accounts->first() : null,
            'location_resource' => null,
            'discovery_error' => null,
        ]);

        return ['locations' => $locations, 'selected' => false];
    }

    /** @return array<int, array<string, mixed>> */
    public function listLocations(?string $account = null): array
    {
        $cacheKey = 'google-business-locations-'.hash('sha256', $this->credentials->clientId().'|'.($this->connection()?->connected_email ?? '').'|'.($account ?? 'all'));

        return Cache::remember($cacheKey, now()->addMinutes(5), fn (): array => $this->fetchLocations($account));
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchLocations(?string $account): array
    {
        $accounts = $account ? [['name' => $account]] : $this->listAccounts();
        $locations = [];

        foreach ($accounts as $accountData) {
            $accountResource = $accountData['name'] ?? null;
            if (blank($accountResource)) {
                continue;
            }

            $accountId = Str::afterLast($accountResource, '/');
            $pageToken = null;
            do {
                $page = $this->request('GET', "https://mybusinessbusinessinformation.googleapis.com/v1/accounts/{$accountId}/locations", array_filter([
                    'readMask' => 'name,title,phoneNumbers,websiteUri,storefrontAddress,regularHours,specialHours,serviceItems,profile',
                    'pageSize' => 100,
                    'pageToken' => $pageToken,
                ]));

                foreach ($page['locations'] ?? [] as $location) {
                    $location['accountResource'] = $accountResource;
                    $location['accountName'] = $accountData['accountName'] ?? $accountResource;
                    $locations[] = $location;
                }

                $pageToken = $page['nextPageToken'] ?? null;
            } while ($pageToken);
        }

        return $locations;
    }

    /** @return array<int, array<string, mixed>> */
    private function listAccounts(): array
    {
        $cacheKey = 'google-business-accounts-'.hash('sha256', $this->credentials->clientId().'|'.($this->connection()?->connected_email ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(10), function (): array {
            $accounts = [];
            $pageToken = null;
            do {
                $page = $this->request('GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', array_filter([
                    'pageSize' => 20,
                    'pageToken' => $pageToken,
                ]));
                $accounts = array_merge($accounts, $page['accounts'] ?? []);
                $pageToken = $page['nextPageToken'] ?? null;
            } while ($pageToken);

            return $accounts;
        });
    }

    /**
     * @param  array<string, mixed>  $location
     */
    public function selectLocation(string $accountResource, string $locationResource): void
    {
        $account = collect($this->listAccounts())->firstWhere('name', $accountResource);
        if (! $account || ! Str::startsWith($locationResource, 'locations/')) {
            throw new RuntimeException('That Business Profile location is no longer available to this Google account. Refresh the profile list and choose again.');
        }

        $location = $this->request('GET', 'https://mybusinessbusinessinformation.googleapis.com/v1/'.$locationResource, [
            'readMask' => 'name,title,phoneNumbers,websiteUri,storefrontAddress,regularHours,specialHours,serviceItems,profile',
        ]);
        $location['accountResource'] = $accountResource;
        $location['accountName'] = $account['accountName'] ?? $accountResource;

        $this->saveSelectedLocation($location);
    }

    public function userFacingGoogleError(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException) {
            return Str::limit($exception->getMessage(), 450);
        }

        if (! $exception instanceof RequestException) {
            return 'Google sign-in completed, but profile discovery could not finish. Check the Google Business Profile API setup, then retry profile discovery.';
        }

        $status = $exception->response->status();
        $googleMessage = trim((string) data_get($exception->response->json(), 'error.message'));

        if ($status === 429) {
            $remainingSeconds = $this->discoveryCooldownSeconds();
            $retryMessage = $remainingSeconds > 0
                ? ' The CRM has paused Google API requests for '.($remainingSeconds >= 60 ? ceil($remainingSeconds / 60).' minute(s)' : $remainingSeconds.' seconds').'.'
                : '';
            $quotaDetails = collect(data_get($exception->response->json(), 'error.details', []));
            $quotaMetadata = $quotaDetails
                ->pluck('metadata')
                ->filter(fn (mixed $metadata): bool => is_array($metadata))
                ->reduce(fn (array $metadata, array $item): array => array_merge($metadata, $item), []) ?? [];
            $quotaMetadata = collect($quotaMetadata)
                ->only(['service', 'quota_metric', 'quota_limit', 'consumer'])
                ->map(fn (mixed $value): string => Str::limit((string) $value, 120))
                ->all();
            $quotaViolations = $quotaDetails
                ->flatMap(fn (array $detail): array => $detail['violations'] ?? [])
                ->pluck('description')
                ->filter()
                ->unique()
                ->map(fn (string $description): string => Str::limit($description, 180))
                ->implode('; ');
            $quotaDiagnostic = $quotaMetadata !== []
                ? ' Google quota details: '.collect($quotaMetadata)->map(fn (string $value, string $key): string => $key.'='.$value)->implode('; ').'.'
                : ($quotaViolations !== '' ? ' Google quota details: '.$quotaViolations.'.' : '');

            if (str_contains(Str::lower($googleMessage), 'not been granted') || str_contains(Str::lower($googleMessage), 'quota 0') || str_contains(Str::lower($googleMessage), 'not approved')) {
                return 'Google Business Profile API access is not approved for this Cloud project. Apply for Basic API Access and confirm the project has a non-zero quota before retrying. Reconnecting the account cannot grant API access. Google says: '.Str::limit($googleMessage, 300);
            }

            return 'Google returned HTTP 429 for Business Profile API quota. The CRM is backing off before it allows another request. If this continues after the cooldown, check the Cloud project quota: Google documents that 0 QPM means API access has not been approved; an approved project normally shows 300 QPM. Confirm the required APIs are enabled in the same project as the OAuth client, then retry. Reconnecting will not increase quota.'.($quotaDiagnostic ? $quotaDiagnostic : '').($retryMessage ? $retryMessage : '');
        }

        if ($status === 403 && (str_contains(Str::lower($googleMessage), 'has not been used') || str_contains(Str::lower($googleMessage), 'accessnotconfigured'))) {
            return 'Google blocked account discovery because an API is not enabled or approved for the OAuth Cloud project. Enable the named API in the same Google Cloud project as this OAuth client, confirm Business Profile API access is approved, then retry. Google says: '.Str::limit($googleMessage, 450);
        }

        if ($status === 403) {
            return 'Google denied access to the Business Profile account (HTTP 403). Confirm the Google login is an owner or manager of the profile and that API access is approved. Google says: '.Str::limit($googleMessage, 450);
        }

        if ($status === 401) {
            return 'Google rejected the authorization (HTTP 401). Reconnect the Google account and verify the OAuth client credentials and Business Profile scope.';
        }

        return 'Google returned HTTP '.$status.' during profile discovery.'.($googleMessage ? ' Google says: '.Str::limit($googleMessage, 450) : ' Check the OAuth client and enabled APIs.');
    }

    /**
     * @param  array<string, mixed>  $location
     */
    private function saveSelectedLocation(array $location): void
    {
        $connection = $this->connection() ?? throw new RuntimeException('Connect a Google account first.');
        $accountResource = $location['accountResource'] ?? null;
        $locationResource = $location['name'] ?? null;

        if (blank($accountResource) || blank($locationResource)) {
            throw new RuntimeException('Google returned a location without its account resource name.');
        }

        $connection->update([
            'account_resource' => $accountResource,
            'location_resource' => $locationResource,
            'discovery_error' => null,
        ]);

        $this->syncBusinessProfileFromGoogle($location);
    }

    /** @param array<string, mixed> $location */
    private function syncBusinessProfileFromGoogle(array $location): void
    {
        $dayNames = [
            'MONDAY' => 'Monday',
            'TUESDAY' => 'Tuesday',
            'WEDNESDAY' => 'Wednesday',
            'THURSDAY' => 'Thursday',
            'FRIDAY' => 'Friday',
            'SATURDAY' => 'Saturday',
            'SUNDAY' => 'Sunday',
        ];
        $regularHours = array_fill_keys(array_values($dayNames), 'Closed');

        foreach ($location['regularHours']['periods'] ?? [] as $period) {
            $day = $dayNames[$period['openDay'] ?? ''] ?? null;
            if ($day) {
                $regularHours[$day] = $this->formatHours($period['openTime'] ?? [], $period['closeTime'] ?? []);
            }
        }

        $specialHours = [];
        foreach ($location['specialHours']['specialHourPeriods'] ?? [] as $period) {
            $date = $period['startDate'] ?? null;
            if (! is_array($date) || ! isset($date['year'], $date['month'], $date['day'])) {
                continue;
            }

            $key = sprintf('%04d-%02d-%02d', $date['year'], $date['month'], $date['day']);
            $specialHours[$key] = ($period['closed'] ?? false)
                ? 'Closed'
                : $this->formatHours($period['openTime'] ?? [], $period['closeTime'] ?? []);
        }

        $address = $location['storefrontAddress'] ?? [];
        $addressLines = array_filter(array_merge(
            $address['addressLines'] ?? [],
            [$address['locality'] ?? null, $address['administrativeArea'] ?? null, $address['postalCode'] ?? null],
        ));
        $services = collect($location['serviceItems'] ?? [])
            ->map(fn (array $item): ?string => $item['freeFormServiceItem']['label']['displayName'] ?? $item['structuredServiceItem']['description'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $profile = BusinessProfile::query()->first() ?? new BusinessProfile;
        $profile->fill([
            'business_name' => $location['title'] ?? $profile->business_name,
            'phone' => $location['phoneNumbers']['primaryPhone'] ?? null,
            'website' => $location['websiteUri'] ?? null,
            'address' => implode(', ', $addressLines),
            'description' => $location['profile']['description'] ?? null,
            'regular_hours' => $regularHours,
            'special_hours' => $specialHours,
            'services' => $services,
        ])->save();
    }

    /**
     * @param  array{hours?: int, minutes?: int, seconds?: int, nanos?: int}  $open
     * @param  array{hours?: int, minutes?: int, seconds?: int, nanos?: int}  $close
     */
    private function formatHours(array $open, array $close): string
    {
        return sprintf('%02d:%02d - %02d:%02d', $open['hours'] ?? 0, $open['minutes'] ?? 0, $close['hours'] ?? 0, $close['minutes'] ?? 0);
    }

    public function updateProfile(BusinessProfile $profile): array
    {
        $connection = $this->requireLocation();
        $payload = ['title' => $profile->business_name];
        if (filled($profile->phone)) {
            $payload['phoneNumbers'] = ['primaryPhone' => $profile->phone, 'additionalPhones' => []];
        }
        if (filled($profile->website)) {
            $payload['websiteUri'] = $profile->website;
        }
        if (filled($profile->address)) {
            $payload['storefrontAddress'] = ['regionCode' => 'IN', 'languageCode' => 'en', 'addressLines' => [$profile->address]];
        }
        if (filled($profile->description)) {
            $payload['profile'] = ['description' => $profile->description];
        }

        $regularHours = [];
        foreach (($profile->regular_hours ?? []) as $day => $hours) {
            if (blank($hours) || Str::lower($hours) === 'closed' || ! preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hours, $matches)) {
                continue;
            }
            $day = Str::upper($day);
            $regularHours[] = ['openDay' => $day, 'closeDay' => $day, 'openTime' => $matches[1], 'closeTime' => $matches[2]];
        }
        if ($regularHours !== []) {
            $payload['regularHours'] = ['periods' => $regularHours];
        }

        $specialHours = [];
        foreach (($profile->special_hours ?? []) as $date => $hours) {
            if (Str::lower($hours) === 'closed') {
                $specialHours[] = ['startDate' => ['year' => date('Y', strtotime($date)), 'month' => date('n', strtotime($date)), 'day' => date('j', strtotime($date))], 'closed' => true];

                continue;
            }
            if (preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $hours, $matches)) {
                $specialHours[] = ['startDate' => ['year' => date('Y', strtotime($date)), 'month' => date('n', strtotime($date)), 'day' => date('j', strtotime($date))], 'openTime' => $matches[1], 'closeTime' => $matches[2]];
            }
        }
        if ($specialHours !== []) {
            $payload['specialHours'] = ['specialHourPeriods' => $specialHours];
        }

        $services = [];
        foreach (($profile->services ?? []) as $service) {
            if (filled($service)) {
                $services[] = ['freeFormServiceItem' => ['category' => 'gcid:car_rental_agency', 'label' => ['languageCode' => 'en', 'displayName' => Str::limit($service, 140)]]];
            }
        }
        if ($services !== []) {
            $payload['serviceItems'] = $services;
        }

        return $this->request('PATCH', 'https://mybusinessbusinessinformation.googleapis.com/v1/'.$connection->location_resource, $payload, [
            'updateMask' => 'title,phoneNumbers,websiteUri,storefrontAddress,profile,regularHours,specialHours,serviceItems',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function syncReviews(): array
    {
        $reviews = [];
        $pageToken = null;
        do {
            $page = $this->request('GET', 'https://mybusiness.googleapis.com/v4/'.$this->locationPath().'/reviews', array_filter([
                'pageSize' => 50,
                'orderBy' => 'updateTime desc',
                'pageToken' => $pageToken,
            ]));

            foreach ($page['reviews'] ?? [] as $review) {
                $reviews[] = $review;
                GoogleReview::query()->updateOrCreate(['google_resource_name' => $review['name']], [
                    'reviewer_name' => $review['reviewer']['displayName'] ?? 'Google reviewer',
                    'star_rating' => $review['starRating'] ?? null,
                    'comment' => $review['comment'] ?? null,
                    'reviewed_at' => $review['createTime'] ?? null,
                    'reply' => $review['reviewReply']['comment'] ?? null,
                    'replied_at' => $review['reviewReply']['updateTime'] ?? null,
                    'google_synced_at' => now(),
                ]);
            }

            $pageToken = $page['nextPageToken'] ?? null;
        } while ($pageToken);

        return $reviews;
    }

    public function replyToReview(GoogleReview $review, string $reply): void
    {
        $this->request('PUT', 'https://mybusiness.googleapis.com/v4/'.$review->google_resource_name.'/reply', ['comment' => $reply]);
        $review->update(['reply' => $reply, 'replied_at' => now(), 'google_synced_at' => now()]);
    }

    public function deleteReviewReply(GoogleReview $review): void
    {
        $this->request('DELETE', 'https://mybusiness.googleapis.com/v4/'.$review->google_resource_name.'/reply');
        $review->update(['reply' => null, 'replied_at' => null, 'google_synced_at' => now()]);
    }

    public function publishPost(GooglePost $post): array
    {
        $payload = ['languageCode' => 'en', 'summary' => $post->summary, 'topicType' => match ($post->post_type) {
            'offer' => 'OFFER', 'event' => 'EVENT', default => 'STANDARD'
        }];
        if ($post->scheduled_at) {
            $payload['scheduledTime'] = $post->scheduled_at->toIso8601String();
        }
        if ($post->call_to_action) {
            $payload['callToAction'] = array_filter(['actionType' => $post->call_to_action, 'url' => $post->call_to_action_url]);
        }
        if ($post->post_type === 'offer') {
            $payload['offer'] = array_filter(['couponCode' => $post->coupon_code, 'redeemOnlineUrl' => $post->call_to_action_url, 'termsConditions' => 'Valid '.$post->offer_start_date?->format('d M Y').' - '.$post->offer_end_date?->format('d M Y')]);
        }
        if ($post->post_type === 'event' && $post->event_start_at && $post->event_end_at) {
            $payload['event'] = [
                'title' => $post->title,
                'schedule' => [
                    'startDate' => ['year' => $post->event_start_at->format('Y'), 'month' => (int) $post->event_start_at->format('n'), 'day' => (int) $post->event_start_at->format('j')],
                    'startTime' => ['hours' => (int) $post->event_start_at->format('G'), 'minutes' => (int) $post->event_start_at->format('i')],
                    'endDate' => ['year' => $post->event_end_at->format('Y'), 'month' => (int) $post->event_end_at->format('n'), 'day' => (int) $post->event_end_at->format('j')],
                    'endTime' => ['hours' => (int) $post->event_end_at->format('G'), 'minutes' => (int) $post->event_end_at->format('i')],
                ],
            ];
        }
        if ($post->photo_path) {
            $payload['media'] = [['mediaFormat' => 'PHOTO', 'sourceUrl' => url(Storage::disk('public')->url($post->photo_path))]];
        }
        $result = $this->request('POST', 'https://mybusiness.googleapis.com/v4/'.$this->locationPath().'/localPosts', $payload);
        $post->update(['google_resource_name' => $result['name'] ?? null, 'status' => $post->scheduled_at ? 'scheduled' : 'published', 'published_at' => $post->scheduled_at ? null : now(), 'google_synced_at' => now()]);

        return $result;
    }

    public function deletePost(GooglePost $post): void
    {
        if ($post->google_resource_name) {
            $this->request('DELETE', 'https://mybusiness.googleapis.com/v4/'.$post->google_resource_name);
        }
        $post->update(['status' => 'deleted', 'google_synced_at' => now()]);
    }

    /** @return array<int, array<string, mixed>> */
    public function syncMedia(): array
    {
        $items = [];
        $pageToken = null;
        do {
            $page = $this->request('GET', 'https://mybusiness.googleapis.com/v4/'.$this->locationPath().'/media', array_filter([
                'pageSize' => 100,
                'pageToken' => $pageToken,
            ]));

            foreach ($page['mediaItems'] ?? [] as $item) {
                $items[] = $item;
                GoogleMedia::query()->updateOrCreate(['google_resource_name' => $item['name']], [
                    'category' => $item['locationAssociation']['category'] ?? 'car',
                    'media_format' => $item['mediaFormat'] ?? null,
                    'google_url' => $item['googleUrl'] ?? null,
                    'google_synced_at' => now(),
                ]);
            }

            $pageToken = $page['nextPageToken'] ?? null;
        } while ($pageToken);

        return $items;
    }

    public function publishMedia(GoogleMedia $media): array
    {
        $result = $this->request('POST', 'https://mybusiness.googleapis.com/v4/'.$this->locationPath().'/media', [
            'mediaFormat' => $media->media_format ?: 'PHOTO',
            'sourceUrl' => url(Storage::disk('public')->url($media->local_path)),
            'locationAssociation' => ['category' => match ($media->category) {
                'exterior' => 'EXTERIOR', 'interior' => 'INTERIOR', 'team' => 'TEAMS', default => 'PRODUCT'
            }],
        ]);
        $media->update(['google_resource_name' => $result['name'] ?? $media->google_resource_name, 'google_url' => $result['googleUrl'] ?? null, 'google_synced_at' => now()]);

        return $result;
    }

    public function deleteMedia(GoogleMedia $media): void
    {
        $this->request('DELETE', 'https://mybusiness.googleapis.com/v4/'.$media->google_resource_name);
        $media->delete();
    }

    /** @return array<string, mixed> */
    public function fetchInsights(string $startDate, string $endDate): array
    {
        $location = Str::afterLast($this->requireLocation()->location_resource, '/');
        $metrics = ['CALL_CLICKS', 'BUSINESS_DIRECTION_REQUESTS', 'WEBSITE_CLICKS', 'BUSINESS_IMPRESSIONS_DESKTOP_MAPS', 'BUSINESS_IMPRESSIONS_MOBILE_MAPS', 'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH', 'BUSINESS_IMPRESSIONS_MOBILE_SEARCH'];
        $query = [];
        foreach (['start_date' => $startDate, 'end_date' => $endDate] as $field => $value) {
            $date = new DateTimeImmutable($value);
            $query["daily_range.{$field}.year"] = $date->format('Y');
            $query["daily_range.{$field}.month"] = $date->format('n');
            $query["daily_range.{$field}.day"] = $date->format('j');
        }
        $queryString = implode('&', array_map(fn (string $metric): string => 'dailyMetrics='.rawurlencode($metric), $metrics));
        $queryString .= '&'.http_build_query($query);

        return $this->request('GET', "https://businessprofileperformance.googleapis.com/v1/locations/{$location}:fetchMultiDailyMetricsTimeSeries?{$queryString}");
    }

    /** @return array{calls: int, directions: int, website: int, views: int}|null */
    public function performanceTotals(int $days = 28): ?array
    {
        $connection = $this->connection();
        if (! $connection || blank($connection->location_resource)) {
            return null;
        }

        $endDate = now()->subDay()->toDateString();
        $startDate = now()->subDays($days)->toDateString();
        $response = Cache::remember('google-business-performance-'.$connection->location_resource.'-'.$endDate, now()->addHours(20), fn (): array => $this->fetchInsights($startDate, $endDate));
        $totals = ['calls' => 0, 'directions' => 0, 'website' => 0, 'views' => 0];
        $mapping = [
            'CALL_CLICKS' => 'calls',
            'BUSINESS_DIRECTION_REQUESTS' => 'directions',
            'WEBSITE_CLICKS' => 'website',
            'BUSINESS_IMPRESSIONS_DESKTOP_MAPS' => 'views',
            'BUSINESS_IMPRESSIONS_MOBILE_MAPS' => 'views',
            'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH' => 'views',
            'BUSINESS_IMPRESSIONS_MOBILE_SEARCH' => 'views',
        ];

        foreach ($response['multiDailyMetricTimeSeries'] ?? [] as $series) {
            $key = $mapping[$series['dailyMetric'] ?? ''] ?? null;
            if ($key) {
                foreach ($series['timeSeries']['datedValues'] ?? [] as $point) {
                    $totals[$key] += (int) ($point['value'] ?? 0);
                }
            }
        }

        return $totals;
    }

    private function locationPath(): string
    {
        $connection = $this->requireLocation();

        return $connection->account_resource.'/locations/'.Str::afterLast($connection->location_resource, '/');
    }

    private function requireLocation(): GoogleBusinessConnection
    {
        $connection = $this->connection() ?? throw new RuntimeException('Connect Google Business Profile first.');
        if (blank($connection->account_resource) || blank($connection->location_resource)) {
            throw new RuntimeException('Select a Google Business Profile location first.');
        }

        return $connection;
    }

    /** @return array<string, mixed> */
    private function request(string $method, string $url, array $data = [], array $query = []): array
    {
        $connection = $this->connection() ?? throw new RuntimeException('Connect Google Business Profile first.');
        $cooldown = $this->discoveryCooldownSeconds();
        if ($cooldown > 0) {
            throw new RuntimeException('Google Business Profile API is cooling down after a quota response. Retry in '.($cooldown >= 60 ? ceil($cooldown / 60).' minute(s)' : $cooldown.' seconds').'.');
        }

        if ($connection->expires_at?->isBefore(now()->addMinutes(5))) {
            if (blank($connection->refresh_token)) {
                throw new RuntimeException('The Google connection expired. Connect Google again.');
            }
            $tokens = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->credentials->clientId(),
                'client_secret' => $this->credentials->clientSecret(),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ])->throw()->json();
            $connection->update(['access_token' => $tokens['access_token'], 'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600))]);
        }
        $options = Str::upper($method) === 'GET' ? ['query' => $data] : ['query' => $query, 'json' => $data];
        $response = Http::withToken($connection->access_token)->acceptJson()->connectTimeout(5)->timeout(30)->send($method, $url, $options);

        if ($response->status() === 429) {
            $this->startDiscoveryCooldown($response->header('Retry-After'));
        } elseif ($response->successful()) {
            Cache::forget($this->discoveryCooldownKey());
            Cache::forget($this->discoveryCooldownKey().'-failures');
        }

        return $response->throw()->json() ?? [];
    }

    public function discoveryCooldownSeconds(): int
    {
        $until = Cache::get($this->discoveryCooldownKey());

        return is_numeric($until) ? max(0, (int) $until - now()->timestamp) : 0;
    }

    private function startDiscoveryCooldown(?string $retryAfter): void
    {
        $failureKey = $this->discoveryCooldownKey().'-failures';
        $failures = (int) Cache::get($failureKey, 0) + 1;
        Cache::put($failureKey, $failures, now()->addDay());

        $retryAt = now()->addSeconds(min(3600, 60 * (2 ** min($failures - 1, 6)) + random_int(0, 15)));

        if (is_numeric($retryAfter)) {
            $retryAt = now()->addSeconds(max(60, min(86400, (int) $retryAfter)));
        } elseif (filled($retryAfter)) {
            try {
                $parsedRetryAt = new DateTimeImmutable($retryAfter);
                $retryAt = now()->setTimestamp(max(
                    now()->addMinute()->timestamp,
                    min(now()->addDay()->timestamp, $parsedRetryAt->getTimestamp()),
                ));
            } catch (Throwable) {
                // Ignore malformed Retry-After values and use the exponential delay.
            }
        }

        Cache::put($this->discoveryCooldownKey(), $retryAt->timestamp, $retryAt);
    }

    private function discoveryCooldownKey(): string
    {
        return 'google-business-api-cooldown-'.hash('sha256', $this->credentials->clientId());
    }
}
