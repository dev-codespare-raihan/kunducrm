<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebsiteContentResource;
use App\Models\TourPackage;
use App\Models\WebsiteCar;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\JsonResponse;

class WebsiteContentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WebsiteSettingsService $settings): JsonResponse
    {
        $websiteSettings = $settings->get();

        return response()->json([
            'data' => [
                'cars' => WebsiteContentResource::collection(WebsiteCar::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get())->resolve(),
                'tours' => WebsiteContentResource::collection(TourPackage::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get())->resolve(),
                'pages' => [
                    'cars' => $websiteSettings['cars_page'],
                    'tours' => $websiteSettings['tours_page'],
                ],
            ],
        ])->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=600');
    }
}
