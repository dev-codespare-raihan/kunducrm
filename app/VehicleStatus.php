<?php

namespace App;

enum VehicleStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case OnTrip = 'on_trip';
    case Maintenance = 'maintenance';
}
