<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($this->route('variant'))],
            'price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'integer'],
            'weight' => ['nullable', 'numeric'],
            'status' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Duplicate variant SKU.',
        ];
    }
}
