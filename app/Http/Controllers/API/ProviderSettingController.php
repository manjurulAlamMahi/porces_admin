<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderSettingController extends Controller
{
    use ApiResponse;
    
    public function shopAvailabilityStatus()
    {
        $auth = Auth::guard('api')->user();

        if(!$auth){
            return $this->error('Unauthenticated', 401);
        }

        $auth->provider_shop_availability_status = $auth->provider_shop_availability_status === 'on' ? 'off' : 'on';
        $auth->save();

        // Update all provider's shops status
        Shop::where('provider_id', $auth->id)
            ->update(['is_active' => $auth->provider_shop_availability_status === 'on']);

        return $this->success('success', $auth->provider_shop_availability_status);
    }

    public function notificationStatus()
    {
        $auth = Auth::guard('api')->user();

        if(!$auth){
            return $this->error('Unauthenticated', 401);
        }

        $auth->provider_notification_status = $auth->provider_notification_status === 'on' ? 'off' : 'on';
        $auth->save();

        return $this->success('success', $auth->provider_notification_status);
    }

    public function autoOrderAcceptStatus()
    {
        $auth = Auth::guard('api')->user();

        if(!$auth){
            return $this->error('Unauthenticated', 401);
        }

        $auth->provider_auto_order_accept_status = $auth->provider_auto_order_accept_status === 'on' ? 'off' : 'on';
        $auth->save();

        return $this->success('success', $auth->provider_auto_order_accept_status);
    }
}
