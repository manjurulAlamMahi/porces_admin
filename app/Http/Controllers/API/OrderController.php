<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ApiResponse;
    // Make Order
    public function makeOrder(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'booking_id'            => 'required|exists:bookings,id',
            'shop_id'               => 'required|exists:shops,id',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|integer|min:1',
            'tip_amount'            => 'nullable|numeric|min:0',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors(), $validation->errors()->first(), 200);
        }
        $user = Auth::guard('api')->user();
        $authUserRoleName = Auth::guard('api')->user()->getRoleNames()->first();
        if (!$user || $authUserRoleName != 'customer') {
            return $this->error([], 'Unauthenticated Or Invalid Token as Customer', 401);
        }

        $shop = Shop::find($request->shop_id);

        if (!$shop) {
            return $this->error([], 'Shop not found', 200);
        }

        // $customer_id = $shop->customer_id;

        $userId = $user->id;

        DB::beginTransaction();
        try {
            $calculatedTotal = 0;
            $itemsData = [];

            // Check product do exists on that shop
            foreach ($request->products as $p) {
                $product = Product::find($p['product_id']);
                if (!$product) {
                    return $this->error([], 'Product not found: ' . $p['product_id'], 200);
                }
                $shop = Shop::where('id', $request->shop_id)
                    ->whereHas('products', function ($q) use ($product) {
                        $q->where('products.id', $product->id);
                    })->first();
                if (!$shop) {
                    return $this->error([], 'Product does not belong to the specified shop: ' . $p['product_id'], 200);
                }
            }

            foreach ($request->products as $p) {
                $product = Product::find($p['product_id']);
                if (!$product) {
                    return $this->error([], 'Product not found: ' . $p['product_id'], 200);
                }
                $price = $product->price;
                $quantity = (int) $p['quantity'];
                $calculatedTotal += $price * $quantity;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }

            $tip = $request->tip_amount;
            $finalTotal = $calculatedTotal + $tip;

            if ($request->total && abs($request->total - $finalTotal) > 0.01) {
                return $this->error([], 'Total price mismatch' . $request->total . ' != ' . $finalTotal, 400);
            }

            $order = Order::create([
                'user_id'     => $userId,
                'shop_id'     => $request->shop_id,
                'customer_id' => $user->id,
                'booking_id'  => $request->booking_id,
                'products'    => json_encode($request->products),
                'total'       => $finalTotal,
                'subtotal'    => $calculatedTotal,
                'tip_amount'  => $tip,
                'status'      => 'pending',
            ]);

            foreach ($itemsData as $it) {
                OrderItem::create([
                    'order_id'                => $order->id,
                    'service_product_item_id' => $it['product_id'],
                    'quantity'                => $it['quantity'],
                    'price'                   => $it['price'],
                ]);
            }

            // Send notification to provider and auto-accept if enabled
            $shop = Shop::with('provider')->find($request->shop_id);
            if ($shop && $shop->provider) {
                // Send notification only if provider has notifications enabled
                if ($shop->provider->provider_notification_status === 'on') {
                    $shop->provider->notify(new NewOrderNotification($order));
                }

                // Auto-accept order if provider has auto-accept enabled
                if ($shop->provider->provider_auto_order_accept_status === 'on') {
                    $order->update(['status' => 'preparing']);
                }
            }

            DB::commit();
            return $this->success(['order_id' => $order->id], 'Order created', 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // List authenticated customer's orders
    public function getOrders(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }


        $orders = Order::with([
            'items.product:id,name,price',
            'booking:id,booking_date,status'
        ])
            ->whereHas('booking', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'order_id'   => $order->id,
                    'status'     => $order->status,
                    'subtotal'   => $order->subtotal,
                    'tip_amount' => $order->tip_amount,
                    'total'      => $order->total,

                    'booking' => [
                        'booking_id'   => $order->booking->id ?? null,
                        'booking_date' => $order->booking->booking_date ?? null,
                        'status'       => $order->booking->status ?? null,
                    ],

                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_id' => $item->product->id ?? null,
                            'name'       => $item->product->name ?? null,
                            'quantity'   => $item->quantity,
                            'price'      => $item->price,
                        ];
                    }),
                ];
            });

        return $this->success($orders, 'Orders retrieved successfully', 200);
    }

    // Cancel an order belonging to the authenticated customer
    public function cancelOrder(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors(), $validation->errors()->first(), 200);
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $order = Order::with('booking')->find($request->order_id);
        if (! $order) {
            return $this->error([], 'Order not found', 404);
        }

        if (! $order->booking || $order->booking->user_id !== $user->id) {
            return $this->error([], 'Unauthorized', 403);
        }

        if ($order->status !== 'pending') {
            return $this->error([], 'Only pending orders can be cancelled', 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $order->save();

            DB::commit();
            return $this->success([], 'Order cancelled successfully', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error([], 'Failed to cancel order', 500);
        }
    }


    // getProviderOrders
    public function getProviderOrders(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'status'  => 'nullable|in:pending,preparing,delivered,cancelled',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors()->first(), 400);
        }

        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $myshops = Shop::where('provider_id', $user->id)
            ->pluck('id')
            ->toArray();

        if (empty($myshops)) {
            return $this->success([], 'No Shops found for Provider', 200);
        }

        $orders = Order::with([
            'customer:id,name,email,phone',
            'items.product:id,name,price',
            'booking:id,booking_date,status,beach_id',
            'booking.beach:id,name',
            'booking.chairs',
        ])
            ->whereIn('shop_id', $myshops)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'order_id'   => $order->id,
                    'status'     => $order->status,
                    'subtotal'   => $order->subtotal,
                    'tip_amount' => $order->tip_amount,
                    'total'      => $order->total,
                    'created_at' => $order->created_at?->diffForHumans(),

                    'customer' => [
                        'customer_id' => $order->customer?->id,
                        'name'        => $order->customer?->name,
                        'email'       => $order->customer?->email,
                        'phone'       => $order->customer?->phone,
                    ],

                    'beach' => [
                        'beach_id'   => $order->booking?->beach?->id,
                        'beach_name' => $order->booking?->beach?->name,
                    ],

                    'chairs' => $order->booking?->chairs?->map(function ($chair) {
                        return [
                            'chair_id'   => $chair->chair->id,
                            'chair_name' => $chair->chair->code,
                        ];
                    }) ?? [],

                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_id' => $item->product?->id,
                            'name'       => $item->product?->name,
                            'quantity'   => $item->quantity,
                            'price'      => $item->price,
                        ];
                    }),
                ];
            });


        return $this->success($orders, 'Provider Orders retrieved successfully', 200);
    }

    // Update Status
    public function acceptOrder(Request $request)
    {
        return $this->updateOrderStatus($request, 'preparing');
    }

    public function rejectOrder(Request $request)
    {
        return $this->updateOrderStatus($request, 'cancelled');
    }

    public function deliverOrder(Request $request)
    {
        return $this->updateOrderStatus($request, 'delivered');
    }

    public function updateOrderStatus(Request $request, $status)
    {
        $validation = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors()->first(), 400);
        }

        $order = Order::find($request->order_id);

        $order->status = $status;
        $order->save();

        return $this->success([], 'Order status updated successfully', 200);
    }
}
