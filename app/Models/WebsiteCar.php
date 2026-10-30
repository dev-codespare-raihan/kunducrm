<?php

namespace App\Models;

use Database\Factories\WebsiteCarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteCar extends Model
{
    /** @use HasFactory<WebsiteCarFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name', 'type', 'seats', 'fuel', 'local_label', 'best_for', 'image_path', 'image_url', 'image_alt', 'image_width', 'image_height', 'blurb', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
