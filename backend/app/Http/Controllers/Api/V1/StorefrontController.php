<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class StorefrontController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $store = DB::table('store_settings')
                ->select(['store_name', 'layout', 'is_active'])
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if (!$store) {
                $store = DB::table('store_settings')
                    ->select(['store_name', 'layout', 'is_active'])
                    ->orderByDesc('is_active')
                    ->orderBy('id')
                    ->first();
            }
        } catch (QueryException) {
            $store = null;
        }

        $layout = strtolower((string) ($store->layout ?? 'ladies'));

        if (!in_array($layout, ['ladies', 'grocery'], true)) {
            $layout = 'ladies';
        }

        return response()->json([
            'success' => true,
            'store' => [
                'name' => (string) ($store->store_name ?? 'Kumkum Novelty Store'),
                'layout' => $layout,
            ],
        ]);
    }
}
