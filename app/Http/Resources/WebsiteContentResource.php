<?php

namespace App\Http\Resources;

use App\Models\WebsiteCar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WebsiteContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image_path ? Storage::disk('public')->url($this->image_path) : $this->image_url;

        return $this->resource instanceof WebsiteCar ? [
            'id' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'seats' => $this->seats,
            'fuel' => $this->fuel,
            'local_label' => $this->local_label,
            'best_for' => $this->best_for,
            'image' => $imageUrl,
            'alt' => $this->image_alt,
            'width' => $this->image_width,
            'height' => $this->image_height,
            'blurb' => $this->blurb,
        ] : [
            'id' => $this->slug,
            'name' => $this->name,
            'span' => $this->duration,
            'from' => $this->price_label,
            'car' => $this->car,
            'image' => $imageUrl,
            'alt' => $this->image_alt,
            'stops' => $this->stops ?? [],
            'text' => $this->description,
        ];
    }
}
