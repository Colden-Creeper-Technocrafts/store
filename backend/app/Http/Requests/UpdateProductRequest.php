<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slugSource = $this->filled('slug') ? $this->input('slug') : $this->input('name');
        $slug = Str::slug((string) $slugSource) ?: 'product';

        $this->merge([
            'slug' => $slug,
        ]);
    }

    public function rules(): array
    {
        $productId = $this->productId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'integer'],
            'weight' => ['nullable', 'numeric'],
            'status' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Duplicate product error.',
            'sku.unique' => 'Duplicate product SKU.',
        ];
    }

    private function productId(): ?int
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return $product->id;
        }

        return is_numeric($product) ? (int) $product : null;
    }
}
