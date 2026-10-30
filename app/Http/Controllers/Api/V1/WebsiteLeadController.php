<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebsiteLeadRequest;
use App\LeadStatus;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class WebsiteLeadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreWebsiteLeadRequest $request): JsonResponse
    {
        $lead = Lead::query()->create([
            ...$request->validated(),
            'source' => 'website',
            'status' => LeadStatus::New,
        ]);

        return response()->json([
            'message' => 'Your inquiry has been received. We will contact you shortly.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
