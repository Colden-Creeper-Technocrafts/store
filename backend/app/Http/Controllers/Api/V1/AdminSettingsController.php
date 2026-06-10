<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController
{
    private function resolveLogoUrl(?string $path, Request $request): ?string
    {
        if (!$path) return null;
        // Already a full URL (legacy / externally hosted)
        if (str_starts_with($path, 'http')) return $path;
        return rtrim($request->root(), '/') . Storage::url($path);
    }

    public function index(Request $request): JsonResponse
    {
        $settings = StoreSetting::orderBy('id')->get()->map(function (StoreSetting $s) use ($request) {
            $arr = $s->toArray();
            $arr['logo_url'] = $this->resolveLogoUrl($s->logo_url, $request);
            return $arr;
        });

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'store_name'             => ['sometimes', 'required', 'string', 'max:255'],
            'business_type'          => ['nullable', 'string', 'max:50'],
            'store_email'            => ['nullable', 'email', 'max:255'],
            'store_phone'            => ['nullable', 'string', 'max:50'],
            'store_description'      => ['nullable', 'string', 'max:2000'],
            'tagline'                => ['nullable', 'string', 'max:255'],
            'currency'               => ['nullable', 'string', 'size:3'],
            'is_active'              => ['sometimes', 'boolean'],
            'banner_title'           => ['nullable', 'string', 'max:255'],
            'banner_text'            => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = StoreSetting::find($id);

        if (!$settings) {
            return response()->json(['message' => 'Store settings not found.'], 404);
        }

        if (!empty($data['is_active'])) {
            StoreSetting::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $settings->update($data);

        // Handle explicit logo removal (logo_url: null sent in payload)
        if ($request->exists('logo_url') && $request->input('logo_url') === null) {
            $this->deleteStoredFile($settings->fresh()->logo_url);
            $settings->update(['logo_url' => null]);
        }

        // Handle explicit banner image removal (banner_image: null sent in payload)
        if ($request->exists('banner_image') && $request->input('banner_image') === null) {
            $this->deleteStoredFile($settings->fresh()->banner_image);
            $settings->update(['banner_image' => null]);
        }

        $fresh = $settings->fresh();
        $arr   = $fresh->toArray();
        $arr['logo_url']     = $this->resolveLogoUrl($fresh->logo_url, $request);
        $arr['banner_image'] = $this->resolveLogoUrl($fresh->banner_image, $request);

        return response()->json(['settings' => $arr]);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function uploadLogo(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:3072'],
        ]);

        $settings = StoreSetting::find($id);

        if (!$settings) {
            return response()->json(['message' => 'Store settings not found.'], 404);
        }

        $this->deleteStoredFile($settings->logo_url);

        $path = $request->file('logo')->store('logos', 'public');
        $settings->update(['logo_url' => $path]);

        return response()->json([
            'logo_url' => $this->resolveLogoUrl($path, $request),
        ]);
    }

    public function uploadBannerImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'banner_image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:6144'],
        ]);

        $settings = StoreSetting::find($id);

        if (!$settings) {
            return response()->json(['message' => 'Store settings not found.'], 404);
        }

        $this->deleteStoredFile($settings->banner_image);

        $path = $request->file('banner_image')->store('banners', 'public');
        $settings->update(['banner_image' => $path]);

        return response()->json([
            'banner_image' => $this->resolveLogoUrl($path, $request),
        ]);
    }
}
