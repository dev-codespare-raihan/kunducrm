<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebsiteSettingsResource;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\JsonResponse;

class WebsiteSettingsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WebsiteSettingsService $settings): JsonResponse
    {
        return (new WebsiteSettingsResource($settings->get()))
            ->response()
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=60');
    }
}
