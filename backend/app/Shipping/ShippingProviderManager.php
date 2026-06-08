<?php

namespace App\Shipping;

use App\Models\ShippingProvider;
use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\Providers\DelhiveryProvider;
use App\Shipping\Providers\ManualShippingProvider;
use App\Shipping\Providers\ShiprocketProvider;
use InvalidArgumentException;

class ShippingProviderManager
{
    /** Map of slug → concrete provider class. */
    private array $driverMap = [
        'shiprocket' => ShiprocketProvider::class,
        'delhivery'  => DelhiveryProvider::class,
        'manual'     => ManualShippingProvider::class,
    ];

    /** Per-request cache of resolved provider instances. */
    private array $resolved = [];

    /**
     * Resolve a provider instance by slug.
     *
     * @throws InvalidArgumentException  if the slug has no registered driver
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  if not in DB
     */
    public function provider(string $slug): ShippingProviderInterface
    {
        if (!isset($this->resolved[$slug])) {
            $class = $this->driverMap[$slug]
                ?? throw new InvalidArgumentException("No shipping driver registered for slug [{$slug}].");

            $model = ShippingProvider::where('slug', $slug)->firstOrFail();

            $this->resolved[$slug] = new $class($model);
        }

        return $this->resolved[$slug];
    }

    /**
     * Return all active, available provider instances ordered by sort_order.
     *
     * @return ShippingProviderInterface[]
     */
    public function activeProviders(): array
    {
        return ShippingProvider::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('slug')
            ->map(fn(string $slug) => $this->provider($slug))
            ->all();
    }

    /**
     * Register a custom driver class for a given slug at runtime.
     * Useful for testing or third-party extensions.
     */
    public function extend(string $slug, string $class): void
    {
        $this->driverMap[$slug] = $class;
        unset($this->resolved[$slug]); // invalidate cached instance
    }
}
