<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['google_resource_name', 'category', 'media_format', 'google_url', 'local_path', 'google_synced_at'])]
class GoogleMedia extends Model
{
    protected function casts(): array
    {
        return [
            'google_synced_at' => 'datetime',
        ];
    }
}
