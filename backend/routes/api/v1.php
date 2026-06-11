<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\AdminNotificationController;
use App\Http\Controllers\Api\V1\AdminSettingsController;
use App\Http\Controllers\Api\V1\AdminCustomerController;
use App\Http\Controllers\Api\V1\AdminOrderController;
use App\Http\Controllers\Api\V1\AdminShippingController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RazorpayController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StorefrontController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/backstore/login', [AuthController::class, 'backstoreLogin']);
Route::get('/storefront', [StorefrontController::class, 'show']);
Route::get('/storefront/categories', [StorefrontController::class, 'categories']);
Route::get('/storefront/coupons', [StorefrontController::class, 'coupons']);
Route::get('/storefront/products', [StorefrontController::class, 'products']);
Route::get('/storefront/products/{slug}', [StorefrontController::class, 'productDetail']);
Route::post('/checkout/guest', [CheckoutController::class, 'guestCheckout']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);
Route::post('/coupons/validate', [CouponController::class, 'validateCode']);

// OTP — public endpoints
Route::post('/otp/send',          [OtpController::class, 'send']);
Route::post('/otp/verify',        [OtpController::class, 'verify']);
Route::post('/otp/login/send',    [OtpController::class, 'loginSend']);
Route::post('/otp/login/verify',  [OtpController::class, 'loginVerify']);
Route::get('/otp/verify-email',   [OtpController::class, 'verifyEmail']);

// Razorpay — public so guest checkouts work; ownership is validated inside each method
Route::post('/payments/razorpay/create-order', [RazorpayController::class, 'createOrder']);
Route::post('/payments/razorpay/verify', [RazorpayController::class, 'verify']);
Route::post('/payments/razorpay/webhook', [RazorpayController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::apiResource('/categories', CategoryController::class);
    Route::post('/categories/{id}/image', [CategoryController::class, 'uploadImage']);
    Route::apiResource('/products', \App\Http\Controllers\Api\V1\ProductController::class);
    Route::patch('/admin/products/{id}/stock', [\App\Http\Controllers\Api\V1\ProductController::class, 'adjustStock']);
    Route::get('/products/images/defaults', [\App\Http\Controllers\Api\V1\ProductImageController::class, 'defaults']);
    Route::apiResource('/products.images', \App\Http\Controllers\Api\V1\ProductImageController::class)->except(['show']);
    Route::apiResource('/products.variants', \App\Http\Controllers\Api\V1\ProductVariantController::class);

    // Cart
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Checkout
    Route::post('/checkout', [CheckoutController::class, 'store']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Coupons (admin CRUD)
    Route::apiResource('/coupons', CouponController::class);

    // Admin analytics
    Route::get('/admin/analytics/summary', [AdminAnalyticsController::class, 'summary']);

    // Admin settings
    Route::get('/admin/settings', [AdminSettingsController::class, 'index']);
    Route::put('/admin/settings/{id}', [AdminSettingsController::class, 'update']);
    Route::post('/admin/settings/{id}/logo', [AdminSettingsController::class, 'uploadLogo']);
    Route::post('/admin/settings/{id}/banner-image', [AdminSettingsController::class, 'uploadBannerImage']);

    // Admin order management
    Route::prefix('admin/orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/{id}/status', [AdminOrderController::class, 'updateStatus']);
        Route::patch('/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
        Route::patch('/{id}/tracking', [AdminOrderController::class, 'updateTracking']);
        Route::patch('/{id}/notes', [AdminOrderController::class, 'updateNotes']);
        Route::patch('/{id}/return-status', [AdminOrderController::class, 'updateReturnStatus']);
    });

    // Admin shipping management
    Route::prefix('admin/shipping')->group(function () {
        // Providers
        Route::get('/providers', [AdminShippingController::class, 'providers']);
        Route::put('/providers/{id}', [AdminShippingController::class, 'updateProvider']);
        Route::post('/providers/{id}/validate', [AdminShippingController::class, 'validateProvider']);

        // Zones
        Route::get('/zones', [AdminShippingController::class, 'zones']);
        Route::post('/zones', [AdminShippingController::class, 'storeZone']);
        Route::put('/zones/{id}', [AdminShippingController::class, 'updateZone']);
        Route::delete('/zones/{id}', [AdminShippingController::class, 'destroyZone']);
        Route::post('/zones/{zoneId}/locations', [AdminShippingController::class, 'storeZoneLocation']);
        Route::delete('/zones/{zoneId}/locations/{locationId}', [AdminShippingController::class, 'destroyZoneLocation']);

        // Methods
        Route::get('/methods', [AdminShippingController::class, 'methods']);
        Route::post('/methods', [AdminShippingController::class, 'storeMethod']);
        Route::put('/methods/{id}', [AdminShippingController::class, 'updateMethod']);
        Route::delete('/methods/{id}', [AdminShippingController::class, 'destroyMethod']);

        // Rates
        Route::get('/methods/{methodId}/rates', [AdminShippingController::class, 'rates']);
        Route::post('/methods/{methodId}/rates', [AdminShippingController::class, 'storeRate']);
        Route::put('/rates/{id}', [AdminShippingController::class, 'updateRate']);
        Route::delete('/rates/{id}', [AdminShippingController::class, 'destroyRate']);
    });

    // Admin customer management
    Route::prefix('admin/customers')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index']);
        Route::get('/{id}', [AdminCustomerController::class, 'show']);
    });

    // Admin notification settings & logs
    Route::prefix('admin/notifications')->group(function () {
        Route::get('/settings', [AdminNotificationController::class, 'show']);
        Route::put('/settings', [AdminNotificationController::class, 'update']);
        Route::get('/logs', [AdminNotificationController::class, 'logs']);
    });

    Route::post('/otp/set-password', [OtpController::class, 'setPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

});
