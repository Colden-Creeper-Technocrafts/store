<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $categoryIds = $this->query('category_ids', []);

        if (!is_array($categoryIds)) {
            $categoryIds = array_filter(explode(',', (string) $categoryIds), 'strlen');
        }

        $this->merge([
            'category_ids' => array_values($categoryIds),
        ]);
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1'],
        ];
    }

    public function categoryIds(): array
    {
        return array_map('intval', $this->validated('category_ids', []));
    }
}
