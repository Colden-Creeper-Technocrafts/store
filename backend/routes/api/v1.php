<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminOrderController;
use App\Http\Controllers\Api\V1\AuthController;
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
Route::get('/storefront/products', [StorefrontController::class, 'products']);
Route::post('/checkout/guest', [CheckoutController::class, 'guestCheckout']);
Route::post('/coupons/validate', [CouponController::class, 'validateCode']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/products', \App\Http\Controllers\Api\V1\ProductController::class);
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

    // Admin order management
    Route::prefix('admin/orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/{id}/status', [AdminOrderController::class, 'updateStatus']);
        Route::patch('/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
        Route::patch('/{id}/tracking', [AdminOrderController::class, 'updateTracking']);
        Route::patch('/{id}/notes', [AdminOrderController::class, 'updateNotes']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

});
