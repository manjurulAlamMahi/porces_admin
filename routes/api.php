<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BeachController;
use App\Http\Controllers\API\BookingChairController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\HelpCenterController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PolicyController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ProviderSettingController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ShopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/user-signup', 'signup');
    Route::post('/user-signin', 'signin');
    Route::post('/user-logout', 'logout');
    Route::post('/user-delete', 'deleteUser');

    // Store FCM Token
    Route::post('/store-user-fcm-token', 'storeFcmToken');
    Route::post('/delete-user-fcm-token', 'deleteFcmToken');

    // OTP verify
    Route::post('/forgot-password', 'sendOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password', 'resetPassword');
});

Route::controller(ProfileController::class)->middleware('auth:api')->group(function () {
    Route::get('/user-profile', 'profile');
    Route::post('/update-user-profile', 'updateProfile');
    Route::post('/change-user-password', 'changePassword');
});

Route::middleware('auth:api')->controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'notification');

    // Mark all read / unread
    Route::post('/notifications/mark-all-read', 'markAllRead');
    Route::post('/notifications/mark-all-unread', 'markAllUnread');

    // Delete all
    Route::post('/notifications/delete-all', 'deleteAll');

    // Single operations
    Route::post('/notifications/delete', 'deleteNotification');
    Route::post('/notifications/mark-read', 'markNotificationRead');
    Route::post('/notifications/mark-unread', 'markNotificationUnread');
});

// Customer API Routes
Route::controller(BeachController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-beaches', 'index');
    Route::get('/get-beach-chairs', 'getBeachChairs');
    Route::get('/get-beach-services', 'getBeachServices');
});

Route::controller(BookingChairController::class)->middleware('auth:api')->group(function () {
    Route::post('/book-chair', 'bookChair');
    Route::get('/get-bookings', 'getBookings');
    Route::post('/check-In-booking', 'checkInBooking');
    Route::post('/cancel-booking', 'cancelBooking');
    Route::get('/booking-receipt/{booking_id}', 'bookingReceipt');
});

Route::controller(HelpCenterController::class)->middleware('auth:api')->group(function () {
    Route::post('/send-message', 'sendMessage');
});

Route::controller(ServiceController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-services', 'getServices');
});

Route::controller(ShopController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-shops/{service_id}/{beach_id}', 'getShopsByService');
});

Route::controller(PolicyController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-policies-beach', 'getBeachPolicy');
    Route::get('/get-policies-disclaimers', 'getDisclaimersPolicy');
});

Route::controller(ProductController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-products/{shop_id}', 'getProductsByShop');
    Route::get('/products-details/{product_id}', 'getProductDetails');
});

Route::controller(OrderController::class)->middleware('auth:api')->group(function () {
    Route::post('/make-order', 'makeOrder');
    Route::get('/get-orders', 'getOrders');
    Route::post('/cancel-order', 'cancelOrder');
});



// Provider API Routes

Route::controller(DashboardController::class)->middleware('auth:api')->group(function () {
    Route::get('/dashboard-provider', 'dashboardProvider');
});
Route::controller(ProviderSettingController::class)->middleware('auth:api')->group(function () {
    Route::get('/provider_shop_availability_status', 'shopAvailabilityStatus');
    Route::get('/provider_notification_status', 'notificationStatus');
    Route::get('/provider_auto_order_accept_status', 'autoOrderAcceptStatus');
});

Route::controller(ShopController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-provider-shops', 'getProviderShops');
    Route::post('/create-shop', 'createShop');
    Route::post('/update-shop', 'updateShop');
    Route::post('/update-provider-shop-status', 'updateShopStatus');
    Route::post('/delete-shop', 'deleteShop');
});

Route::controller(ProductController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-shop-dropdown', 'getShopDropdown');
    Route::get('/get-provider-products', 'getProviderProducts');
    Route::post('/create-product', 'createProduct');
    Route::post('/update-product', 'updateProduct');
    Route::post('/update-product-status', 'updateProductStatus');
    Route::post('/delete-product', 'deleteProduct');
});

Route::controller(OrderController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-provider-orders', 'getProviderOrders');
    Route::post('/accept-provider-orders', 'acceptOrder');
    Route::post('/reject-provider-orders', 'rejectOrder');
    Route::post('/deliver-provider-orders', 'deliverOrder');
});





