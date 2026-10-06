<?php

namespace Database\Seeders;

use App\Models\Beach;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providerId = 3;
        $beaches = Beach::all();
        $services = Service::all();

        foreach ($beaches as $beach) {
            foreach ($services as $service) {
                Shop::create([
                    'provider_id'  => $providerId,
                    'beach_id'     => $beach->id,
                    'service_id'   => $service->id,
                    'name'         => $service->name . ' Shop - ' . $beach->name,
                    'description'  => 'Best ' . strtolower($service->name) . ' service on the beach',
                    'opening_time' => '09:00',
                    'closing_time' => '21:00',
                    'is_active'    => true,
                ]);
            }
        }
    }
}
