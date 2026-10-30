<?php

namespace App\Models;

use Database\Factories\TourPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourPackage extends Model
{
    /** @use HasFactory<TourPackageFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name', 'duration', 'price_label', 'car', 'image_path', 'image_url', 'image_alt', 'description', 'stops', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['stops' => 'array', 'is_published' => 'boolean'];
    }
}
