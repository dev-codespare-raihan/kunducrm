<x-filament-panels::page>
    <div class="mx-auto grid w-full max-w-5xl gap-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Google Business Profile</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Connect the Google account that owns or manages Kundu Tour and Travels. The CRM uses
                        OAuth 2.0 so Google can ask the account owner to grant access.
                    </p>
                </div>
                @if ($googleConnected)
                    <span class="inline-flex items-center gap-2 rounded-full bg-success-50 px-3 py-1.5 text-sm font-medium text-success-700 dark:bg-success-400/10 dark:text-success-300">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                        {{ $profileSelected ? 'Profile connected' : 'Google account connected · profile not selected' }}{{ $connectedEmail ? ' · ' . $connectedEmail : '' }}
                    </span>
                @elseif ($credentialsConfigured)
                    <span class="inline-flex items-center gap-2 rounded-full bg-warning-50 px-3 py-1.5 text-sm font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-5 w-5" />
                        Credentials saved · account not connected
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-minus-circle" class="h-5 w-5" />
                        Setup required
                    </span>
                @endif
            </div>

            @if ($googleConnected && ! $profileSelected)
                <div class="mt-5 rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm leading-6 text-warning-900 dark:border-warning-400/30 dark:bg-warning-400/10 dark:text-warning-100">
                    @if ($discoveryError)
                        <strong>Profile discovery needs attention.</strong> {{ $discoveryError }}
                    @else
                        Google is connected. Use <strong>Retry profile discovery</strong> above. If Google returns multiple locations, choose the Kundu Tour and Travels profile under Google Business Profile → Business Profile.
                    @endif
                </div>
            @endif

            <div class="mt-5 rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm leading-6 text-primary-950 dark:border-primary-400/20 dark:bg-primary-400/10 dark:text-primary-100">
                <strong>There is no Google Business Profile API key to paste here.</strong>
                This CRM needs an OAuth 2.0 Web application Client ID and Client Secret. The secret and
                saved OAuth credentials are encrypted in the CRM database. The Google account signs in
                separately and grants the CRM access; never send credentials in chat or email.
                After consent, the CRM discovers all accessible locations. A single location is selected
                and imported automatically; if Google returns several, choose the Kundu Tour and Travels profile.
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">OAuth Client ID</p>
                    <p class="mt-1 font-semibold {{ $clientIdConfigured ? 'text-success-700 dark:text-success-300' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ $clientIdConfigured ? 'Configured' : 'Missing' }}
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">OAuth Client Secret</p>
                    <p class="mt-1 font-semibold {{ $clientSecretConfigured ? 'text-success-700 dark:text-success-300' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ $clientSecretConfigured ? 'Stored securely' : 'Missing' }}
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Google callback URL</p>
                    <code class="mt-1 block break-all text-xs text-gray-800 dark:text-gray-200">{{ $callbackUri }}</code>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-400/15 dark:text-primary-300">1</span>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Get Business Profile API access</h2>
            </div>
            <ol class="mt-4 ml-11 list-decimal space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                <li>Use a Google account that is an owner or manager of the business profile. Google currently requires the profile to be verified and active for at least 60 days, with the business website listed on the profile. Follow Google's prerequisite to set up a Business Profile Organization account for API access.</li>
                <li><a class="font-medium text-primary-600 underline dark:text-primary-400" href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Create or select a Google Cloud project</a>. Note its project number from the project dashboard.</li>
                <li>Submit the <a class="font-medium text-primary-600 underline dark:text-primary-400" href="https://support.google.com/business/contact/api_default" target="_blank" rel="noopener noreferrer">Business Profile API access request</a>. Choose Basic API Access and provide the project number, business website, and an account email that manages the profile. Wait for Google to approve the project before continuing.</li>
                <li>After approval, enable the APIs used by this CRM in the <a class="font-medium text-primary-600 underline dark:text-primary-400" href="https://console.cloud.google.com/apis/library" target="_blank" rel="noopener noreferrer">Google Cloud API Library</a>: Google My Business API (posts, reviews, and media), My Business Account Management API, My Business Business Information API, and Business Profile Performance API (insights).</li>
            </ol>
            <p class="mt-4 ml-11 text-xs leading-5 text-gray-500 dark:text-gray-400">
                Google controls API approval and quotas. Saving OAuth credentials alone does not grant API access.
                For a 403, enable the specific API named in Google's error in this same Cloud project. For a 429, wait at least a minute and use Retry profile discovery. Google's quota page explains that a zero quota means API access has not been approved. See the <a class="underline" href="https://developers.google.com/my-business/content/prereqs" target="_blank" rel="noopener noreferrer">official prerequisites</a>, <a class="underline" href="https://developers.google.com/my-business/content/basic-setup" target="_blank" rel="noopener noreferrer">API setup guide</a>, and <a class="underline" href="https://developers.google.com/my-business/content/limits" target="_blank" rel="noopener noreferrer">quota limits</a>.
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-400/15 dark:text-primary-300">2</span>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Create OAuth web credentials</h2>
            </div>
            <ol class="mt-4 ml-11 list-decimal space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                <li>Open <a class="font-medium text-primary-600 underline dark:text-primary-400" href="https://console.cloud.google.com/auth/overview" target="_blank" rel="noopener noreferrer">Google Auth Platform</a>. Complete the branding, audience, support email, and developer contact details for the consent screen.</li>
                <li>For an external app in Testing, add the Google account that will connect the profile as a test user. Before relying on unattended/daily sync in production, publish the consent screen and complete any verification Google requires for the requested Business Profile scope. External Testing refresh tokens for this scope expire after seven days.</li>
                <li>Go to <a class="font-medium text-primary-600 underline dark:text-primary-400" href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Clients</a>, create an OAuth client ID, and choose <strong>Web application</strong>.</li>
                <li>Add the exact callback URL shown above under <strong>Authorized redirect URIs</strong>, then create the client. Copy its Client ID and Client Secret.</li>
            </ol>
            <p class="mt-4 ml-11 text-xs leading-5 text-gray-500 dark:text-gray-400">
                The CRM requests the <code>https://www.googleapis.com/auth/business.manage</code> scope to manage the profile with the owner's consent. Read Google's <a class="underline" href="https://developers.google.com/my-business/content/implement-oauth" target="_blank" rel="noopener noreferrer">OAuth guide</a> and <a class="underline" href="https://developers.google.com/identity/protocols/oauth2" target="_blank" rel="noopener noreferrer">token lifecycle guidance</a>.
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-400/15 dark:text-primary-300">3</span>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Save credentials and connect the profile</h2>
            </div>
            <ol class="mt-4 ml-11 list-decimal space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                <li>Select <strong>Configure Google credentials</strong> at the top of this page.</li>
                <li>Paste the OAuth Client ID and Client Secret. Confirm the callback URL exactly matches the authorized redirect URI in Google Cloud, then save. The secret is encrypted at rest and is never shown again; leave the secret field blank to keep its saved value.</li>
                <li>Select <strong>Connect with Google</strong>. Sign in with the Google account that manages Kundu Tour and Travels, review the requested access, and approve it.</li>
                <li>In <strong>Google Business Profile → Business Profile</strong>, select the correct business location. Then sync reviews or insights to confirm the connection.</li>
            </ol>
            <p class="mt-4 ml-11 text-xs leading-5 text-gray-500 dark:text-gray-400">
                The callback URI must be publicly reachable in production and use HTTPS. If the application URL changes, update the OAuth client's authorized redirect URI and save the matching URI here.
            </p>
        </section>
    </div>
</x-filament-panels::page>
