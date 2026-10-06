<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Shop;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    use ApiResponse;
    // getShopsByService
    public function getShopsByService($service_id , $beach_id)
    {
        if($service_id == null){
            return $this->error([], 'Service ID is required.' , 400);
        }

        if($beach_id == null){
            return $this->error([], 'Beach ID is required.' , 400);
        }

        $service = Service::find($service_id);
        if($service == null){
            return $this->error([], 'Invalid service ID.' , 400);
        }

        $shops = Shop::where('service_id', $service_id)
            ->where('beach_id', $beach_id)
            ->where('is_active', true)
            ->whereHas('provider', function($q) {
                $q->where('provider_shop_availability_status', 'on');
            })
            ->latest()->get();

        $shops = $shops->map(function ($shop) {
            return [
                'id'   => $shop->id,
                'name' => $shop->name,
                'star' => $shop->ratings,
                'opening_time' => $shop->opening_time,
                'closing_time' => $shop->closing_time,
                'image' => asset($shop->image),
                'total_products' => $shop->products()->count(),
            ];
        });

        return $this->success($shops , 'Shops fetched successfully.' , 200);
    }

    public function getProviderShops(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'search' => 'sometimes|string|max:255',
        ]);

        $user = Auth::guard('api')->user();

        if($user == null || !$user->hasRole('provider')){
            return $this->error([], 'Only providers can access their shops.' , 403);
        }

        $shops = Shop::where('provider_id', $user->id)->latest()->get();

        if ($request->has('search')) {
            $search = $request->input('search');
            $shops = $shops->filter(function ($shop) use ($search) {
                return stripos($shop->name, $search) !== false;
            });
        }

        $shops = $shops->map(function ($shop) {
            return [
                'id'   => $shop->id,
                'name' => $shop->name,
                'star' => $shop->ratings,
                'opening_time' => $shop->opening_time,
                'closing_time' => $shop->closing_time,
                'image' => asset($shop->image),
                'total_products' => $shop->products()->count(),
            ];
        });

        return $this->success($shops , 'Shops fetched successfully.' , 200);
    }

    // createShop
    public function createShop(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'service_id'   => 'required|exists:services,id',
            'opening_time' => 'required',
            'closing_time' => 'required',
            'ratings'      => 'nullable|string|max:5',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first() , 400);
        }

        $user = Auth::guard('api')->user();

        if($user == null || !$user->hasRole('provider')){
            return $this->error([], 'Only providers can create shops.' , 403);
        }

        $provider_id = $user->id;
        $beach_id = $user->beach_id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->uploadImage($request->file('image'), null, 'uploads/shops', 300, 300, 'shop_image');
        } else {
            return $this->error([], 'Image upload failed.' , 400);
        }

        $shop = Shop::create([
            'provider_id'  => $provider_id,
            'beach_id'     => $beach_id,
            'service_id'   => $request->service_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'ratings'      => $request->ratings,
            'image'        => $imagePath,
            'is_active'    => $request->is_active ?? true,
        ]);

        return $this->success([] , 'Shop created successfully.' , 201);
    }

    //updateShop
    public function updateShop(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id'           => 'required|exists:shops,id',
            'name'         => 'nullable|string|max:255',
            'service_id'   => 'nullable|exists:services,id',
            'opening_time' => 'nullable',
            'closing_time' => 'nullable',
            'ratings'      => 'nullable|string|max:5',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first() , 400);
        }

        $user = Auth::guard('api')->user();

        if($user == null || !$user->hasRole('provider')){
            return $this->error([], 'Only providers can update their shops.' , 403);
        }

        $shop = Shop::where('id', $request->id)->where('provider_id', $user->id)->first();

        if (!$shop) {
            return $this->error([], 'You do not own this shop.' , 403);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->uploadImage($request->file('image'), null, 'uploads/shops', 300, 300, 'shop_image');
        } else {
            $imagePath = $shop->image;
        }

        $shop->update([
            'service_id'   => $request->service_id,
            'name'         => $request->name,
            'description'  => $request->description,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'ratings'      => $request->ratings,
            'image'        => $imagePath,
            'is_active'    => $request->is_active ?? $shop->is_active,
        ]);

        return $this->success([] , 'Shop updated successfully.' , 200);
    }

    //updateShopStatus
    public function updateShopStatus(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id'        => 'required|exists:shops,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first() , 400);
        }

        $user = Auth::guard('api')->user();

        if($user == null || !$user->hasRole('provider')){
            return $this->error([], 'Only providers can update their shops.' , 403);
        }

        $shop = Shop::where('id', $request->id)->where('provider_id', $user->id)->first();

        if (!$shop) {
            return $this->error([], 'You do not own this shop.' , 403);
        }

        $shop->update([
            'is_active' => $request->is_active,
        ]);

        return $this->success([] , 'Shop status updated successfully.' , 200);
    }

    public function deleteShop(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id' => 'required|exists:shops,id',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first() , 400);
        }

        $user = Auth::guard('api')->user();

        if($user == null || !$user->hasRole('provider')){
            return $this->error([], 'Only providers can delete their shops.' , 403);
        }

        $shop = Shop::where('id', $request->id)->where('provider_id', $user->id)->first();

        if (!$shop) {
            return $this->error([], 'You do not own this shop.' , 403);
        }

        $shop->delete();

        return $this->success([] , 'Shop deleted successfully.' , 200);
    }

}
