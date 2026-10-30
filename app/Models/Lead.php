<?php

namespace App\Models;

use App\LeadStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'phone', 'email', 'source', 'status', 'trip_type', 'vehicle_category', 'pickup_location', 'destination', 'pickup_at', 'return_at', 'passengers', 'budget', 'notes', 'assigned_to', 'customer_id', 'last_contacted_at'])]
class Lead extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'pickup_at' => 'datetime',
            'return_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'budget' => 'decimal:2',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
