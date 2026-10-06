<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Booking;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->get();

        $shops = Shop::with('products')->get();
        $bookings = Booking::all();

        if ($customers->isEmpty() || $shops->isEmpty() || $bookings->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'preparing', 'delivered', 'cancelled'];

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $shop = $shops->random();
            $booking = $bookings->where('user_id', $customer->id)->first() ?? $bookings->random();
            
            if ($shop->products->isEmpty()) continue;

            $products = $shop->products->random(rand(1, min(3, $shop->products->count())));
            
            $subtotal = 0;
            $orderData = [];
            
            foreach ($products as $product) {
                $quantity = rand(1, 3);
                $price = $product->price;
                $subtotal += $price * $quantity;
                
                $orderData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price
                ];
            }
            
            $tipAmount = rand(0, 10);
            $total = $subtotal + $tipAmount;

            $order = Order::create([
                'booking_id' => $booking->id,
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'status' => $statuses[array_rand($statuses)],
                'subtotal' => $subtotal,
                'total' => $total,
                'tip_amount' => $tipAmount,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            foreach ($orderData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_product_item_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }
    }
}