<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use ApiResponse;
    // Get Dashboard Data for Provider
    public function dashboardProvider(Request $request)
    {
        $auth = Auth::guard('api')->user();

        if (! $auth) {
            return $this->error([], 'Unauthorized', 401);
        }

        // ✅ Validate input
        $validate = Validator::make($request->all(), [
            'year'  => 'required|integer|min:2000|max:' . now()->year,
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        if ($validate->fails()) {
            return $this->error([], $validate->errors()->first(), 400);
        }

        $year  = $request->year;
        $month = $request->month;

        $shopIds = Shop::where('provider_id', $auth->id)->pluck('id');

        // Total earnings
        $totalEarnings = Order::whereIn('shop_id', $shopIds)
            ->where('status', 'delivered')
            ->sum('total');

        // Pending earnings
        $pendingEarnings = Order::whereIn('shop_id', $shopIds)
            ->whereIn('status', ['pending', 'preparing'])
            ->sum('total');

        // Active products
        $activeItems = Product::where('is_active', true)
            ->whereIn('shop_id', $shopIds)
            ->count();

        // -------------------------
        // Yearly sales (by month)
        // -------------------------
        $yearlySales = [];

        for ($i = 1; $i <= 12; $i++) {
            $yearlySales[date('M', mktime(0, 0, 0, $i, 1))] =
                Order::whereIn('shop_id', $shopIds)
                ->where('status', 'delivered')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $i)
                ->count();
        }

        // -------------------------
        // Monthly sales (by day)
        // Only if month is provided
        // -------------------------
        $monthlySales = [];

        if ($month) {
            $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $monthlySales[$day] =
                    Order::whereIn('shop_id', $shopIds)
                    ->where('status', 'delivered')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->whereDay('created_at', $day)
                    ->count();
            }
        }

        return $this->success([
            'filters' => [
                'year'  => $year,
                'month' => $month,
            ],
            'total_earnings'   => $totalEarnings,
            'pending_earnings' => $pendingEarnings,
            'active_items'     => $activeItems,
            'yearly_sales'     => $yearlySales,
            'monthly_sales'    => $monthlySales,
        ], 'Dashboard data fetched successfully', 200);
    }
}
