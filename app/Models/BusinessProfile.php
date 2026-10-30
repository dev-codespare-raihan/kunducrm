<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['business_name', 'phone', 'website', 'address', 'description', 'regular_hours', 'special_hours', 'services'])]
class BusinessProfile extends Model
{
    protected function casts(): array
    {
        return [
            'regular_hours' => 'array',
            'special_hours' => 'array',
            'services' => 'array',
        ];
    }
}
