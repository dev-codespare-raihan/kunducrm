<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'summary', 'post_type', 'call_to_action', 'call_to_action_url', 'coupon_code', 'offer_start_date', 'offer_end_date', 'event_start_at', 'event_end_at', 'photo_path', 'google_resource_name', 'status', 'scheduled_at', 'published_at', 'google_synced_at', 'created_by'])]
class GooglePost extends Model
{
    protected function casts(): array
    {
        return [
            'offer_start_date' => 'date',
            'offer_end_date' => 'date',
            'event_start_at' => 'datetime',
            'event_end_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'google_synced_at' => 'datetime',
        ];
    }
}
