<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['google_resource_name', 'reviewer_name', 'star_rating', 'comment', 'reviewed_at', 'reply', 'replied_at', 'google_synced_at'])]
class GoogleReview extends Model
{
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'replied_at' => 'datetime',
            'google_synced_at' => 'datetime',
        ];
    }
}
