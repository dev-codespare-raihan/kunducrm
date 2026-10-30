<?php

namespace App;

enum BookingStatus: string
{
    case Quote = 'quote';
    case Confirmed = 'confirmed';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
