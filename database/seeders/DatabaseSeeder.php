<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RoleSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SubCategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\GiveawaySeeder;
use Database\Seeders\EntryPackageSeeder;
use Database\Seeders\DeliveryTypeSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BeachSeeder::class,
            UserSeeder::class,
            ChairsSeeder::class,
            BeachServiceSeeder::class,
            ServiceSeeder::class,
            ShopSeeder::class,
            ProductSeeder::class,
            NotificationSeeder::class,
            OrderSeeder::class,
            CompanySettingsSeeder::class,
            DynamicPageSeeder::class,
        ]);


    }
}
