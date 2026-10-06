<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shops = Shop::with('service')->get();

        foreach ($shops as $shop) {

            $products = [];

            switch (strtolower($shop->service->name)) {

                case 'food':
                    $products = [
                        ['name' => 'Burger', 'price' => 8.50],
                        ['name' => 'Pizza', 'price' => 12.00],
                        ['name' => 'Hot Dog', 'price' => 6.00],
                        ['name' => 'Fries', 'price' => 4.00],
                    ];
                    break;

                case 'drinks':
                    $products = [
                        ['name' => 'Water Bottle', 'price' => 2.00],
                        ['name' => 'Coca Cola', 'price' => 3.00],
                        ['name' => 'Orange Juice', 'price' => 4.50],
                        ['name' => 'Iced Coffee', 'price' => 5.00],
                    ];
                    break;

                case 'massage':
                    $products = [
                        ['name' => '15 min Massage', 'price' => 20.00],
                        ['name' => '30 min Massage', 'price' => 35.00],
                        ['name' => '60 min Massage', 'price' => 60.00],
                    ];
                    break;

                case 'water sports':
                    $products = [
                        ['name' => 'Jet Ski (15 min)', 'price' => 50.00],
                        ['name' => 'Banana Boat Ride', 'price' => 30.00],
                        ['name' => 'Parasailing', 'price' => 80.00],
                    ];
                    break;

                default:
                    continue 2;
            }

            foreach ($products as $key => $product) {
                Product::create([
                    'shop_id'          => $shop->id,
                    'name'             => $product['name'],
                    'sku'              => strtoupper(Str::slug($product['name'], '-')) . '-' . $shop->id,
                    'description'      => $product['name'] . ' from ' . $shop->name,
                    'price'            => $product['price'],
                    'available_qty'    => 9999,
                    'preparation_time' => $key % 2 == 0 ? '10-15 mins' : '20-30 mins',
                    'is_active'        => true,
                    'is_famous'        => $key % 2 == 0 ? true : false,
                ]);
            }
        }
    }
}
