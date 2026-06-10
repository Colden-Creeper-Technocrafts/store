<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $categories)
    {
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function resolveImageUrl(?string $path, Request $request): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return rtrim($request->root(), '/') . Storage::url($path);
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
                    'image' => $category->image,
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
            $store = $this->categories->resolveActiveStore();

            if (!$store) {
                return response()->json([ 'success' => true, 'categories' => [] ]);
            }

            $categories = $this->categories->allForStore((int) $store->id);

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
        $store = $this->categories->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = $this->categories->findForStore((int) $store->id, $id);

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $store = $this->categories->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = $this->categories->createForStore((int) $store->id, $request->validated());

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $store = $this->categories->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = $this->categories->findForStore((int) $store->id, $id);

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        // Delete stored file when image is explicitly cleared
        if ($request->exists('image') && $request->input('image') === null) {
            $this->deleteStoredImage($category->image ?? null);
        }

        $category = $this->categories->update($category, $request->validated());

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:3072'],
        ]);

        $store = $this->categories->resolveActiveStore();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found.'], 404);
        }

        $category = $this->categories->findForStore((int) $store->id, $id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $this->deleteStoredImage($category->image ?? null);

        $path = $request->file('image')->store('categories', 'public');
        $category->update(['image' => $path]);

        return response()->json([
            'success' => true,
            'image'   => $this->resolveImageUrl($path, $request),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->categories->resolveActiveStore();

        if (!$store) {
            return response()->json([ 'success' => false, 'message' => 'Store not found' ], 404);
        }

        $category = $this->categories->findForStore((int) $store->id, $id);

        if (!$category) {
            return response()->json([ 'success' => false, 'message' => 'Category not found' ], 404);
        }

        $this->categories->delete($category);

        return response()->json(['success' => true]);
    }
}
