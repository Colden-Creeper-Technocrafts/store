<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController
{
    public function show(): JsonResponse
    {
        $settings = StoreSetting::active();

        if (!$settings) {
            return response()->json(['settings' => null], 404);
        }

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_name'        => ['sometimes', 'required', 'string', 'max:255'],
            'business_type'     => ['nullable', 'string', 'max:50'],
            'store_email'       => ['nullable', 'email', 'max:255'],
            'store_phone'       => ['nullable', 'string', 'max:50'],
            'store_description' => ['nullable', 'string', 'max:2000'],
            'currency'          => ['nullable', 'string', 'size:3'],
            'features'          => ['nullable', 'array'],
            'features.reviews'      => ['boolean'],
            'features.wishlist'     => ['boolean'],
            'features.subscriptions'=> ['boolean'],
            'features.loyalty'      => ['boolean'],
        ]);

        $settings = StoreSetting::active();

        if (!$settings) {
            $settings = StoreSetting::create(array_merge(['store_name' => 'My Store', 'is_active' => true], $data));
        } else {
            $settings->update($data);
        }

        return response()->json(['settings' => $settings->fresh()]);
    }
}
