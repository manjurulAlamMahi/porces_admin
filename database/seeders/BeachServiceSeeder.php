<?php

namespace Database\Seeders;

use App\Models\Beach;
use App\Models\BeachService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BeachServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Umbrella', 'price' => 15.00 , 'image' => 'uploads/beaches/services/umbrella.jpg'],
            ['name' => 'Towel',    'price' => 5.00, 'image' => 'uploads/beaches/services/towel.jpg'],
            ['name' => 'Pillow',   'price' => 8.00, 'image' => 'uploads/beaches/services/pillow.jpg'],
            ['name' => 'SPF',      'price' => 12.00, 'image' => 'uploads/beaches/services/spf.jpg'],
            ['name' => 'Wifi',     'price' => 3.00, 'image' => 'uploads/beaches/services/wifi.jpg'],
        ];

        $beaches = Beach::all();

        foreach ($beaches as $beach) {
            foreach ($services as $service) {
                BeachService::create([
                    'beach_id' => $beach->id,
                    'name'     => $service['name'],
                    'price'    => $service['price'],
                    'image'    => $service['image'],
                ]);
            }
        }
    }
}
