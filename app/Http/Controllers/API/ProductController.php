<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // API Response
    use ApiResponse;

    public function getShopDropdown()
    {
        $user = Auth::guard('api')->user();
        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can access their shops.', 403);
        }

        $shops = Shop::where('provider_id', $user->id)->latest()->get();

        $shops = $shops->map(function ($shop) {
            return [
                'id'   => $shop->id,
                'name' => $shop->name,
            ];
        });

        return $this->success($shops, 'Shops fetched successfully.', 200);
    }

    public function getProductsByShop(Request $request, $shop_id)
    {
        $products = Product::where('shop_id', $shop_id);

        if ($request->is_famous == 'true') {
            $products = $products->where('is_famous', true);
        }

        if ($request->is_active == 'true') {
            $products = $products->where('is_active', true);
        }

        if($request->search != null){
            $products = $products->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $products->latest()->get();

        if ($products->isEmpty()) {
            return $this->error('No products found for this shop.', 200);
        }

        $products = $products->map(function ($product) {
            return [
                'id'          => $product->id,
                'name'        => $product->name,
                'price'       => $product->price,
                'image_url'   => asset($product->image_url),
                'is_famous'   => $product->is_famous ? true : false,
            ];
        });

        $shop_info = Shop::find($shop_id);

        $shop_info = [
            'id'           => $shop_info->id,
            'name'         => $shop_info->name,
            'description'  => $shop_info->description,
            'opening_time' => $shop_info->opening_time,
            'closing_time' => $shop_info->closing_time,
            'star'         => rand(1, 5),
            'image_url'    => asset($shop_info->image),
        ];

        $data = [
            'shop_info' => $shop_info,
            'products'  => $products,
        ];


        return $this->success($data, 'Products retrieved successfully.', 200);
    }

    public function getProductDetails($product_id)
    {
        $product = Product::find($product_id);

        if (!$product) {
            return $this->error('Product not found.', 200);
        }

        $productDetails = [
            'id'               => $product->id,
            'name'             => $product->name,
            'description'      => $product->description,
            'price'            => $product->price,
            'preparation_time' => '15-20 mins',
            'available_qty'    => $product->available_qty,
            'image_url'        => asset($product->image_url),
        ];

        return $this->success($productDetails, 'Product details retrieved successfully.', 200);
    }

    public function getProviderProducts(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $user = Auth::guard('api')->user();

        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can access their products.', 403);
        }

        if ($validate->fails()) {
            return $this->error([], $validate->errors()->first(), 400);
        }

        $provider_id = $user->id;

        if ($request->has('shop_id')) {
            $shops = [$request->shop_id];
        } else {
            $shops = Shop::where('provider_id', $provider_id)->pluck('id')->toArray();
        }

        $products = Product::whereIn('shop_id', $shops)->latest()->get();

        if ($products->isEmpty()) {
            return $this->error([], 'No products found for this provider.', 200);
        }

        $products = $products->map(function ($product) {
            return [
                'id'          => $product->id,
                'image_url'   => asset($product->image_url),
                'name'        => $product->name,
                'description' => $product->description,
                'preparation_time' => $product->preparation_time ?? null,
                'price'       => $product->price,
                'is_active'   => $product->is_active ? true : false,
                'is_famous'   => $product->is_famous ? true : false,
                'shop' => [
                    'id'   => $product->shop->id,
                    'name' => $product->shop->name,
                    'image' => asset($product->shop->image),
                    'star' => $product->shop->ratings,
                ],
            ];
        });


        if ($request->has('shop_id')) {
            $shop_data = Shop::find($request->shop_id);
            $shop_info = [
                'id'           => $shop_data->id,
                'name'         => $shop_data->name,
                'description'  => $shop_data->description,
                'opening_time' => $shop_data->opening_time,
                'closing_time' => $shop_data->closing_time,
                'star'         => $shop_data->ratings,
                'image_url'    => asset($shop_data->image),
            ];
        } else {
            $shop_info = null;
        }

        $data = [
            'total_products' => $products->count(),
            'shop_info'      => $shop_info,
            'products'       => $products,
        ];

        return $this->success($data, 'Products fetched successfully.', 200);
    }

    public function createProduct(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'shop_id'          => 'required|exists:shops,id',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'available_qty'    => 'required|integer|min:0',
            'preparation_time' => 'nullable|string|max:100',
            'is_famous'        => 'nullable|boolean',
            'image_url'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active'        => 'nullable|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first(), 400);
        }

        $user = Auth::guard('api')->user();

        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can create products.', 403);
        }

        $shop = Shop::where('id', $request->shop_id)
            ->where('provider_id', $user->id)
            ->first();

        if (!$shop) {
            return $this->error([], 'You do not own this shop.', 403);
        }

        $product = new Product();
        $product->shop_id = $request->shop_id;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->available_qty = $request->available_qty;
        $product->is_famous = $request->is_famous ?? false;
        $product->is_active = $request->is_active ?? true;

        if ($request->hasFile('image_url')) {
            $imagePath = $this->uploadImage($request->file('image_url'), null, 'uploads/products', 300, 300, 'product_image');
            $product->image_url = $imagePath;
        }

        $product->save();

        return $this->success([], 'Product created successfully.', 201);
    }

    // updateProduct
    public function updateProduct(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id'               => 'required|exists:products,id',
            'shop_id'          => 'required|exists:shops,id',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'available_qty'    => 'required|integer|min:0',
            'preparation_time' => 'nullable|string|max:100',
            'is_famous'        => 'nullable|boolean',
            'image_url'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active'        => 'nullable|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first(), 400);
        }

        $user = Auth::guard('api')->user();

        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can update products.', 403);
        }

        $product = Product::where('id', $request->id)
            ->whereHas('shop', function ($query) use ($user, $request) {
                $query->where('provider_id', $user->id)
                      ->where('id', $request->shop_id);
            })
            ->first();

        if (!$product) {
            return $this->error([], 'You do not own this product.', 403);
        }

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->available_qty = $request->available_qty;
        $product->is_famous = $request->is_famous ?? $product->is_famous;
        $product->is_active = $request->is_active ?? $product->is_active;

        if ($request->hasFile('image_url')) {
            $imagePath = $this->uploadImage($request->file('image_url'), null, 'uploads/products', 300, 300, 'product_image');
            $product->image_url = $imagePath;
        }

        $product->save();

        return $this->success([], 'Product updated successfully.', 200);
    }

    // updateProductStatus
    public function updateProductStatus(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id'        => 'required|exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first(), 400);
        }

        $user = Auth::guard('api')->user();

        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can update products.', 403);
        }

        $product = Product::where('id', $request->id)
            ->whereHas('shop', function ($query) use ($user) {
                $query->where('provider_id', $user->id);
            })
            ->first();

        if (!$product) {
            return $this->error([], 'You do not own this product.', 403);
        }

        $product->update([
            'is_active' => $request->is_active,
        ]);

        return $this->success([], 'Product status updated successfully.', 200);
    }

    // deleteProduct
    public function deleteProduct(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id' => 'required|exists:products,id',
        ]);

        if ($validated->fails()) {
            return $this->error([], $validated->errors()->first(), 400);
        }

        $user = Auth::guard('api')->user();

        if ($user == null || !$user->hasRole('provider')) {
            return $this->error([], 'Only providers can delete products.', 403);
        }

        $product = Product::where('id', $request->id)
            ->whereHas('shop', function ($query) use ($user) {
                $query->where('provider_id', $user->id);
            })
            ->first();
        if (!$product) {
            return $this->error([], 'You do not own this product.', 403);
        }

        $product->delete();

        return $this->success([], 'Product deleted successfully.', 200);
    }
}
