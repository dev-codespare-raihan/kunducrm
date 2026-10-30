<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        BusinessProfile::query()->firstOrCreate([], [
            'business_name' => 'Kundu Tour and Travels',
            'address' => '52/1 Atabagan, Garia, Kolkata',
        ]);

        $this->call(WebsiteContentSeeder::class);
    }
}
