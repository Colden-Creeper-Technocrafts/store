<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slugSource = $this->filled('slug') ? $this->input('slug') : $this->input('name');

        $this->merge([
            'slug' => Str::slug((string) $slugSource) ?: 'category',
        ]);
    }

    public function rules(): array
    {
        $storeId = $this->activeStoreId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'description' => ['nullable', 'string'],
            'parent_category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('store_setting_id', $storeId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Duplicate category error.',
        ];
    }

    private function activeStoreId(): ?int
    {
        $store = DB::table('store_settings')
            ->select(['id'])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$store) {
            $store = DB::table('store_settings')
                ->select(['id'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        return $store ? (int) $store->id : null;
    }
}
