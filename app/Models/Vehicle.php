<?php

namespace App\Models;

use App\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['registration_number', 'make', 'model', 'category', 'year', 'seats', 'daily_rate', 'status', 'notes'])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'year' => 'integer',
            'seats' => 'integer',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
