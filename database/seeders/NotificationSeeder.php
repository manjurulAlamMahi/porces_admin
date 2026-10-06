<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->get();

        $providers = User::whereHas('roles', function($q) {
            $q->where('name', 'provider');
        })->get();

        // Demo notifications for customers
        $customerNotifications = [
            [
                'title' => 'Welcome to Elaramy!',
                'message' => 'Thank you for joining us. Enjoy your beach experience!',
                'type' => 'welcome'
            ],
            [
                'title' => 'Booking Confirmed',
                'message' => 'Your chair booking has been confirmed for today.',
                'type' => 'booking_confirmed'
            ],
            [
                'title' => 'Order Delivered',
                'message' => 'Your order has been delivered to your location.',
                'type' => 'order_delivered'
            ],
            [
                'title' => 'Special Offer',
                'message' => 'Get 20% off on your next food order!',
                'type' => 'promotion'
            ],
            [
                'title' => 'Payment Successful',
                'message' => 'Your payment of $25.50 has been processed successfully.',
                'type' => 'payment_success'
            ]
        ];

        // Demo notifications for providers
        $providerNotifications = [
            [
                'title' => 'New Order Alert',
                'message' => 'You have received a new order #1001 from John Doe',
                'type' => 'new_order',
                'data' => ['order_id' => 1001, 'customer_name' => 'John Doe']
            ],
            [
                'title' => 'New Order Alert',
                'message' => 'You have received a new order #1002 from Jane Smith',
                'type' => 'new_order',
                'data' => ['order_id' => 1002, 'customer_name' => 'Jane Smith']
            ]
        ];

        // Send notifications to customers
        foreach ($customers as $customer) {
            foreach ($customerNotifications as $notification) {
                $customer->notify(new GeneralNotification(
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['data'] ?? []
                ));
            }
        }

        // Send notifications to providers
        foreach ($providers as $provider) {
            foreach ($providerNotifications as $notification) {
                $provider->notify(new GeneralNotification(
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['data'] ?? []
                ));
            }
        }
    }
}