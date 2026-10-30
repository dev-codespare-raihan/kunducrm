<?php

namespace App\Models;

use App\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable(['booking_number', 'customer_id', 'vehicle_id', 'lead_id', 'assigned_to', 'status', 'trip_type', 'pickup_location', 'destination', 'pickup_at', 'return_at', 'daily_rate', 'total_amount', 'amount_paid', 'notes'])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'pickup_at' => 'datetime',
            'return_at' => 'datetime',
            'daily_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (blank($booking->booking_number)) {
                $booking->booking_number = 'KT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            }
        });

        static::saving(function (Booking $booking): void {
            if ($booking->pickup_at && $booking->return_at && $booking->return_at->lessThanOrEqualTo($booking->pickup_at)) {
                throw ValidationException::withMessages([
                    'data.return_at' => 'The return time must be after pickup.',
                ]);
            }

            if ($booking->vehicle_id && in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Ongoing], true)) {
                $conflictingBookingExists = static::query()
                    ->where('vehicle_id', $booking->vehicle_id)
                    ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Ongoing->value])
                    ->where('pickup_at', '<', $booking->return_at)
                    ->where('return_at', '>', $booking->pickup_at)
                    ->when($booking->exists, fn ($query) => $query->whereKeyNot($booking->getKey()))
                    ->exists();

                if ($conflictingBookingExists) {
                    throw ValidationException::withMessages([
                        'data.vehicle_id' => 'This vehicle already has a confirmed trip during those dates.',
                    ]);
                }
            }

            if ($booking->total_amount <= 0 && $booking->pickup_at && $booking->return_at && $booking->daily_rate > 0) {
                $rentalDays = max(1, (int) ceil($booking->pickup_at->diffInSeconds($booking->return_at) / 86400));
                $booking->total_amount = $rentalDays * (float) $booking->daily_rate;
            }

            if ($booking->amount_paid > $booking->total_amount) {
                throw ValidationException::withMessages([
                    'data.amount_paid' => 'The amount paid cannot exceed the booking total.',
                ]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
