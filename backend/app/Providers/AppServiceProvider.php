<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\UserAddressRepositoryInterface;
use App\Repositories\UserAddressRepository;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\CartRepositoryInterface;
use App\Interfaces\CategoryRepositoryInterface;
use App\Interfaces\CouponRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\ProductImageRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\ProductVariantRepositoryInterface;
use App\Interfaces\StorefrontRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\CartRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\StorefrontRepository;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Shipping\ShippingProviderManager;
use App\Shipping\ShippingRuleEngine;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(ProductImageRepositoryInterface::class, ProductImageRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);
        $this->app->bind(StorefrontRepositoryInterface::class, StorefrontRepository::class);
        $this->app->bind(UserAddressRepositoryInterface::class, UserAddressRepository::class);
        $this->app->singleton(ShippingProviderManager::class);
        $this->app->singleton(ShippingRuleEngine::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
