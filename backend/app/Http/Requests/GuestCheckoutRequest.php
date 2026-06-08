<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:99'],
            'shipping_name'        => ['required', 'string', 'max:255'],
            'shipping_email'       => ['required', 'email', 'max:255'],
            'shipping_phone'       => ['nullable', 'string', 'max:30'],
            'shipping_address'     => ['required', 'string', 'max:500'],
            'shipping_city'        => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_country'     => ['required', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'coupon_code'          => ['nullable', 'string', 'max:64'],
            'shipping_state'       => ['nullable', 'string', 'max:100'],
            'shipping_method_id'   => ['nullable', 'integer', 'exists:shipping_methods,id'],
        ];
    }
}
