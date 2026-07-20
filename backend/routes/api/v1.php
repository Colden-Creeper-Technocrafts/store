<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\AdminNotificationController;
use App\Http\Controllers\Api\V1\AdminSettingsController;
use App\Http\Controllers\Api\V1\AdminCartController;
use App\Http\Controllers\Api\V1\AdminCustomerController;
use App\Http\Controllers\Api\V1\AdminLogsController;
use App\Http\Controllers\Api\V1\AdminUsersController;
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
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\FulfillmentController;
use App\Http\Controllers\Api\V1\StorefrontController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/backstore/login', [AuthController::class, 'backstoreLogin'])->middleware('throttle:5,1');
Route::get('/storefront', [StorefrontController::class, 'show']);
Route::get('/storefront/categories', [StorefrontController::class, 'categories']);
Route::get('/storefront/coupons', [StorefrontController::class, 'coupons']);
Route::get('/storefront/products', [StorefrontController::class, 'products']);
Route::get('/storefront/products/{slug}', [StorefrontController::class, 'productDetail']);
Route::post('/checkout/guest', [CheckoutController::class, 'guestCheckout'])->middleware('throttle:5,1');
Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);
Route::get('/shipping/check', [ShippingController::class, 'check']);
Route::post('/coupons/validate', [CouponController::class, 'validateCode']);

// Profile email verification (public — link arrives via email, no auth header)
Route::get('/profile/verify-email', [AuthController::class, 'verifyEmailChange']);

// OTP — public endpoints
Route::post('/otp/send',          [OtpController::class, 'send'])->middleware('throttle:5,1');
Route::post('/otp/verify',        [OtpController::class, 'verify'])->middleware('throttle:10,1');
Route::post('/otp/login/send',    [OtpController::class, 'loginSend'])->middleware('throttle:5,1');
Route::post('/otp/login/verify',  [OtpController::class, 'loginVerify'])->middleware('throttle:10,1');
Route::get('/otp/verify-email',   [OtpController::class, 'verifyEmail']);

// Razorpay — public so guest checkouts work; ownership is validated inside each method
Route::post('/payments/razorpay/create-order', [RazorpayController::class, 'createOrder']);
Route::post('/payments/razorpay/verify', [RazorpayController::class, 'verify']);
Route::post('/payments/razorpay/webhook', [RazorpayController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Saved addresses
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::patch('/addresses/{id}/default', [AddressController::class, 'setDefault']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

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

    // ── Admin-only routes (requires Admin role) ───────────────────────────
    Route::middleware('admin')->group(function () {

        // Analytics
        Route::get('/admin/analytics/summary', [AdminAnalyticsController::class, 'summary']);

        // Settings
        Route::get('/admin/settings', [AdminSettingsController::class, 'index']);
        Route::put('/admin/settings/{id}', [AdminSettingsController::class, 'update']);
        Route::post('/admin/settings/{id}/logo', [AdminSettingsController::class, 'uploadLogo']);
        Route::post('/admin/settings/{id}/banner-image', [AdminSettingsController::class, 'uploadBannerImage']);
        Route::post('/admin/settings/{id}/favicon', [AdminSettingsController::class, 'uploadFavicon']);

        // Order management
        Route::prefix('admin/orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('/{id}', [AdminOrderController::class, 'show']);
            Route::patch('/{id}/status', [AdminOrderController::class, 'updateStatus']);
            Route::patch('/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
            Route::patch('/{id}/tracking', [AdminOrderController::class, 'updateTracking']);
            Route::patch('/{id}/notes', [AdminOrderController::class, 'updateNotes']);
            Route::patch('/{id}/return-status', [AdminOrderController::class, 'updateReturnStatus']);
            Route::post('/{id}/fulfill', [FulfillmentController::class, 'fulfill']);
            Route::get('/{id}/tracking-live', [FulfillmentController::class, 'tracking']);
        });

        // Shipping management
        Route::prefix('admin/shipping')->group(function () {
            Route::get('/providers', [AdminShippingController::class, 'providers']);
            Route::put('/providers/{id}', [AdminShippingController::class, 'updateProvider']);
            Route::post('/providers/{id}/validate', [AdminShippingController::class, 'validateProvider']);

            Route::get('/zones', [AdminShippingController::class, 'zones']);
            Route::post('/zones', [AdminShippingController::class, 'storeZone']);
            Route::put('/zones/{id}', [AdminShippingController::class, 'updateZone']);
            Route::delete('/zones/{id}', [AdminShippingController::class, 'destroyZone']);
            Route::post('/zones/{zoneId}/locations', [AdminShippingController::class, 'storeZoneLocation']);
            Route::delete('/zones/{zoneId}/locations/{locationId}', [AdminShippingController::class, 'destroyZoneLocation']);

            Route::get('/methods', [AdminShippingController::class, 'methods']);
            Route::post('/methods', [AdminShippingController::class, 'storeMethod']);
            Route::put('/methods/{id}', [AdminShippingController::class, 'updateMethod']);
            Route::delete('/methods/{id}', [AdminShippingController::class, 'destroyMethod']);

            Route::get('/methods/{methodId}/rates', [AdminShippingController::class, 'rates']);
            Route::post('/methods/{methodId}/rates', [AdminShippingController::class, 'storeRate']);
            Route::put('/rates/{id}', [AdminShippingController::class, 'updateRate']);
            Route::delete('/rates/{id}', [AdminShippingController::class, 'destroyRate']);
        });

        // User management (all roles)
        Route::prefix('admin/users')->group(function () {
            Route::get('/', [AdminUsersController::class, 'index']);
            Route::post('/', [AdminUsersController::class, 'store']);
        });

        // Customer management
        Route::prefix('admin/customers')->group(function () {
            Route::get('/', [AdminCustomerController::class, 'index']);
            Route::post('/', [AdminCustomerController::class, 'store']);
            Route::get('/{id}', [AdminCustomerController::class, 'show']);
        });

        // Cart management
        Route::prefix('admin/carts')->group(function () {
            Route::get('/', [AdminCartController::class, 'index']);
            Route::delete('/{id}', [AdminCartController::class, 'destroy']);
        });

        // Log viewer
        Route::prefix('admin/logs')->group(function () {
            Route::get('/files', [AdminLogsController::class, 'files']);
            Route::get('/content', [AdminLogsController::class, 'content']);
        });

        // Notification settings & logs
        Route::prefix('admin/notifications')->group(function () {
            Route::get('/settings', [AdminNotificationController::class, 'show']);
            Route::put('/settings', [AdminNotificationController::class, 'update']);
            Route::get('/logs', [AdminNotificationController::class, 'logs']);
        });

    }); // end middleware('admin')

    Route::post('/otp/set-password', [OtpController::class, 'setPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

}); // end middleware('auth:sanctum')
