<?php

namespace App\Shipping;

use App\Models\ShippingProvider;
use App\Shipping\Contracts\ShippingProviderInterface;

abstract class AbstractShippingProvider implements ShippingProviderInterface
{
    public function __construct(protected ShippingProvider $providerModel) {}

    public function getSlug(): string
    {
        return $this->providerModel->slug;
    }

    public function getName(): string
    {
        return $this->providerModel->name;
    }

    public function isAvailable(): bool
    {
        return $this->providerModel->is_active;
    }

    /** Read a value from the encrypted credentials array. */
    protected function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->providerModel->credentials, $key, $default);
    }

    /** Read a value from the provider settings array. */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->providerModel->settings, $key, $default);
    }

    /** Refresh the in-memory model from the database (e.g. after credential update). */
    public function refresh(): static
    {
        $this->providerModel->refresh();
        return $this;
    }
}
