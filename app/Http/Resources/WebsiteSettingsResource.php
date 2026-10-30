<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'site_name' => $this['site_name'],
            'tagline' => $this['tagline'],
            'seo' => [
                'title' => $this['seo_title'],
                'description' => $this['seo_description'],
                'keywords' => $this['seo_keywords'],
                'robots_index' => (bool) $this['robots_index'],
            ],
            'logo_url' => $this['logo_url'],
            'favicon_url' => $this['favicon_url'],
            'colors' => $this['colors'],
        ];
    }
}
