<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private function resolveActiveStore(): ?object
    {
        $store = DB::table('store_settings')
            ->select(['id', 'store_name', 'layout', 'is_active'])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$store) {
            $store = DB::table('store_settings')
                ->select(['id', 'store_name', 'layout', 'is_active'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        return $store;
    }

    private function buildTree($categories, $parentId = null)
    {
        return $categories
            ->where('parent_category_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(function ($category) use ($categories) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_category_id' => $category->parent_category_id,
                    'sort_order' => $category->sort_order,
                    'is_active' => (bool) $category->is_active,
                    'children' => $this->buildTree($categories, $category->id),
                ];
            })
            ->all();
    }

    public function index(): JsonResponse
    {
        try {
            $store = $this->resolveActiveStore();

            if (!$store) {
                return response()->json([ 'success' => true, 'categories' => [] ]);
            }

            $categories = Category::where('store_setting_id', $store->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'categories' => $this->buildTree($categories),
            ]);
        } catch (QueryException) {
            return response()->json([ 'success' => false, 'categories' => [] ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = Category::where('store_setting_id', $store->id)
            ->where('id', $id)
            ->first();

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'description' => 'nullable|string',
            'parent_category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('store_setting_id', $store->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = Category::create([
            'store_setting_id' => $store->id,
            'parent_category_id' => $payload['parent_category_id'] ?? null,
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = Category::where('store_setting_id', $store->id)
            ->where('id', $id)
            ->first();

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'parent_category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('store_setting_id', $store->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update([
            'parent_category_id' => $payload['parent_category_id'] ?? null,
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = Category::where('store_setting_id', $store->id)
            ->where('id', $id)
            ->first();

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }
}
