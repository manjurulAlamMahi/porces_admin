<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Beach;

class BeachSeeder extends Seeder
{
    public function run()
    {
        $beaches = [
            [ 'name' => 'Isle of Palms', 'slug' => 'isle-of-palms' , 'image' => 'uploads/beaches/isle-of-palms.jpg', 'longitude' => -79.9214, 'latitude' => 32.7800 ],
            [ 'name' => 'Folly Beach', 'slug' => 'folly-beach', 'image' => 'uploads/beaches/folly-beach.jpg', 'longitude' => -79.8500, 'latitude' => 32.6500 ],
            [ 'name' => 'Myrtle Beach', 'slug' => 'myrtle-beach', 'image' => 'uploads/beaches/myrtle-beach.jpg', 'longitude' => -78.9100, 'latitude' => 33.6900 ],
            [ 'name' => 'Tybee Island', 'slug' => 'tybee-island', 'image' => 'uploads/beaches/tybee-island.jpg', 'longitude' => -80.4500, 'latitude' => 31.8400 ],
        ];

        foreach ($beaches as $beach) {
            Beach::create($beach);
        }
    }
}
