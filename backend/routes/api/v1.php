<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\StorefrontController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/backstore/login', [AuthController::class, 'backstoreLogin']);
Route::get('/storefront', [StorefrontController::class, 'show']);
Route::get('/storefront/categories', [StorefrontController::class, 'categories']);
Route::get('/storefront/products', [StorefrontController::class, 'products']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/products', \App\Http\Controllers\Api\V1\ProductController::class);
    Route::apiResource('/products.variants', \App\Http\Controllers\Api\V1\ProductVariantController::class);

    Route::post('/logout', [AuthController::class, 'logout']);

});
