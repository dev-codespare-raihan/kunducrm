<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Google Business Profile performance over the last 28 days.</p>
        </div>

        @if ($loadError)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                {{ $loadError }}
            </div>
        @endif

        @if ($insights)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    'Calls' => $insights['calls'],
                    'Direction requests' => $insights['directions'],
                    'Website clicks' => $insights['website'],
                    'Profile views' => $insights['views'],
                ] as $label => $value)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($value) }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Last 28 days</p>
                    </div>
                @endforeach
            </div>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400">Google reports button clicks for calls and website visits, and daily profile impressions for views. These are Google estimates and may arrive with a delay.</p>
    </div>
</x-filament-panels::page>
