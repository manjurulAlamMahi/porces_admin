<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Service
        //
        $services = [
            ['name' => 'Food', 'slug' => 'food', 'description' => 'Rent an umbrella to stay shaded on the beach.', 'icon' => 'service_icon/1.png'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Comfortable beach chairs available for rent.', 'icon' => 'service_icon/2.png'],
            ['name' => 'Snacks', 'slug' => 'snacks', 'description' => 'Fresh towels provided for your convenience.', 'icon' => 'service_icon/3.png'],
            ['name' => 'Rentals', 'slug' => 'rentals', 'description' => 'Enjoy snacks and drinks right on the beach.', 'icon' => 'service_icon/4.png'],
            ['name' => 'Services', 'slug' => 'services', 'description' => 'Rent equipment for water sports activities.', 'icon' => 'service_icon/5.png'],
            ['name' => 'Merchandise', 'slug' => 'merchandise', 'description' => 'Rent equipment for water sports activities.', 'icon' => 'service_icon/7.png'],
            ['name' => 'Community', 'slug' => 'community', 'description' => 'Rent equipment for water sports activities.', 'icon' => 'service_icon/6.png'],
        ];
        //

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
