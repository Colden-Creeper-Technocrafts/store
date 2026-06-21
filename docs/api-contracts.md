# API Contracts

Base URL: `http://localhost:8000/api/v1`  
All requests/responses are `application/json`.  
Authenticated endpoints require `Authorization: Bearer <token>`.

---

## Response Envelope

Every response has `"success": true | false` at the root.

| Scenario | Shape |
|---|---|
| Single resource | `{ "success": true, "<name>": { ... } }` |
| Collection | `{ "success": true, "<name>s": [...], "meta": { total, per_page, current_page, last_page } }` |
| Action only | `{ "success": true }` |
| Auth success | `{ "success": true, "message": "...", "token": "...", "role": "...", "user": { ... } }` |
| Error | `{ "success": false, "message": "..." }` |
| Validation (422) | `{ "message": "...", "errors": { "field": ["message"] } }` — no `success` key |

---

## Auth

### `POST /register`
```json
// Request
{ "name": "Jane", "email": "jane@example.com", "password": "secret123", "password_confirmation": "secret123" }

// Response 200
{ "success": true, "message": "Registration successful", "token": "...", "role": "Customer",
  "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "Customer" } }
```

### `POST /login`
```json
// Request
{ "email": "jane@example.com", "password": "secret123" }

// Response 200
{ "success": true, "message": "Login successful", "token": "...", "role": "Customer", "user": { ... } }

// Response 401
{ "success": false, "message": "Invalid credentials" }
```

### `POST /backstore/login`
Same body as `/login`. Returns 403 if the user is not an Admin.

### `GET /profile` 🔒
```json
// Response 200
{ "success": true, "user": { "id": 1, "name": "...", "email": "...", "role": "Admin" } }
```

### `POST /logout` 🔒
```json
// Response 200
{ "success": true, "message": "Logout successful" }
```

---

## OTP

### `POST /otp/send` — order OTP (guest checkout)
```json
// Request
{ "phone": "9876543210", "email": "jane@example.com", "order_id": 42 }

// Response 200
{ "success": true, "message": "OTP sent" }
```

### `POST /otp/verify`
```json
// Request
{ "phone": "9876543210", "otp": "123456" }

// Response 200
{ "success": true, "is_new_user": true }

// Response 422
{ "success": false, "message": "Invalid or expired OTP" }
```

### `POST /otp/login/send`
```json
{ "phone": "9876543210" }
// Response 200 — { "success": true }
```

### `POST /otp/login/verify`
```json
// Request
{ "phone": "9876543210", "otp": "123456" }

// Response 200 — same shape as /login
{ "success": true, "token": "...", "role": "Customer", "user": { ... } }
```

### `GET /otp/verify-email?token=<64-char-token>`
Redirects or returns confirmation. Marks `email_verified_at`.

### `POST /otp/set-password` 🔒
```json
{ "password": "newpass", "password_confirmation": "newpass" }
// Response 200 — { "success": true }
```

---

## Storefront (public)

### `GET /storefront`
```json
{
  "success": true,
  "store": {
    "id": 1, "name": "My Store", "currency": "INR",
    "logo_url": null, "banner_image_url": null,
    "banner_title": null, "banner_text": null
  }
}
```

### `GET /storefront/categories`
```json
{ "success": true, "categories": [ { "id": 1, "name": "Bangles", "slug": "bangles", "image": null, "parent_id": null } ] }
```

### `GET /storefront/products?category_ids[]=1&category_ids[]=2`
```json
{
  "success": true,
  "products": [
    {
      "id": 5, "name": "Gold Bangle", "slug": "gold-bangle",
      "sku": "GB-001", "price": "499.00", "sale_price": null,
      "quantity": 20, "image": "http://...", "category_name": "Bangles",
      "short_description": "...",
      "variants": [
        { "id": 10, "sku": "GB-001-S", "price": "499.00", "sale_price": null,
          "quantity": 10, "options": { "Size": "S" }, "is_default": true }
      ]
    }
  ]
}
```

### `GET /storefront/products/{slug}`
Same shape as one item from the list above, wrapped in `"product"`.

### `GET /storefront/coupons`
```json
{ "success": true, "coupons": [ { "code": "SAVE10", "discount_type": "percentage", "discount_value": "10.00", ... } ] }
```

---

## Shipping

### `POST /shipping/calculate`
```json
// Request
{ "pincode": "400001", "state": "Maharashtra", "order_amount": 1500.00 }

// Response 200
{
  "success": true,
  "rates": [
    { "method_id": 3, "method_name": "Standard", "cost": 49.00, "is_free": false, "delivery_estimate": "3–5 days" },
    { "method_id": 4, "method_name": "Express", "cost": 99.00, "is_free": false, "delivery_estimate": "1–2 days" }
  ]
}
```

---

## Coupons

### `POST /coupons/validate`
```json
// Request
{ "code": "SAVE10", "order_amount": 1200.00 }

// Response 200
{ "success": true, "coupon": { "code": "SAVE10", "discount_type": "percentage", "discount_value": 10, "discount_amount": 120.00 } }

// Response 422
{ "success": false, "message": "Coupon has expired" }
```

---

## Cart 🔒

### `GET /cart`
```json
{
  "success": true,
  "cart": {
    "id": 7,
    "items": [
      { "id": 12, "product_id": 5, "quantity": 2, "line_total": "998.00",
        "product": { "id": 5, "name": "Gold Bangle", "price": "499.00", "image": "..." } }
    ],
    "total": "998.00"
  }
}
```

### `POST /cart/items`
```json
// Request
{ "product_id": 5, "quantity": 1 }
// Response 200 — full cart object (same as GET /cart)
```

### `PUT /cart/items/{item}`
```json
// Request
{ "quantity": 3 }
// Response 200 — full cart object
```

### `DELETE /cart/items/{item}` — 200 full cart object
### `DELETE /cart` — `{ "success": true }`

---

## Checkout

### `POST /checkout` 🔒 — authenticated users
### `POST /checkout/guest` — guests

```json
// Request (guest adds items[])
{
  "shipping_name": "Jane Doe",
  "shipping_email": "jane@example.com",
  "shipping_phone": "9876543210",
  "shipping_address": "123 Main St",
  "shipping_city": "Mumbai",
  "shipping_state": "Maharashtra",
  "shipping_postal_code": "400001",
  "shipping_country": "India",
  "notes": null,
  "coupon_code": "SAVE10",
  "shipping_method_id": 3,
  "items": [{ "product_id": 5, "quantity": 2 }]   // guest only
}

// Response 200
{ "success": true, "order": { "id": 42, "total": "878.00", "status": "pending" } }
```

---

## Payments (Razorpay)

### `POST /payments/razorpay/create-order`
```json
// Request
{ "order_id": 42 }

// Response 200
{
  "key": "rzp_test_...",
  "razorpay_order_id": "order_...",
  "amount": 87800,
  "currency": "INR",
  "order_id": 42,
  "name": "My Store",
  "description": "Order #42",
  "prefill": { "name": "Jane Doe", "email": "jane@example.com", "contact": "9876543210" }
}
```

### `POST /payments/razorpay/verify`
```json
// Request
{
  "order_id": 42,
  "razorpay_payment_id": "pay_...",
  "razorpay_order_id": "order_...",
  "razorpay_signature": "..."
}

// Response 200
{ "success": true, "order_id": 42 }

// Response 422
{ "message": "Payment verification failed. Please contact support." }
```

### `POST /payments/razorpay/webhook`
Handled server-side. Validates `X-Razorpay-Signature` header (HMAC-SHA256).

---

## Orders 🔒

### `GET /orders`
```json
{
  "success": true,
  "orders": [ { "id": 42, "status": "processing", "payment_status": "paid", "total": "878.00", "created_at": "..." } ],
  "meta": { "total": 5, "per_page": 15, "current_page": 1, "last_page": 1 }
}
```

### `GET /orders/{order}`
Full order with `items` and `statusHistory` arrays.

---

## Products 🔒 (Admin)

### `GET /products?search=&category_id=&status=1&per_page=20`
```json
{
  "success": true,
  "products": [ { "id": 5, "name": "Gold Bangle", "sku": "GB-001", "price": "499.00", "quantity": 20, ... } ],
  "meta": { "total": 48, "per_page": 20, "current_page": 1, "last_page": 3 }
}
```

### `POST /products`
```json
// Request
{
  "name": "Silver Ring", "slug": "silver-ring", "category_id": 2,
  "sku": "SR-001", "price": 299.00, "sale_price": null,
  "quantity": 50, "status": true, "short_description": "..."
}
// Response 200 — { "success": true, "product": { ... } }
```

### `PUT /products/{id}` — same body, full replace
### `DELETE /products/{id}` — `{ "success": true }`
### `PATCH /admin/products/{id}/stock` — `{ "quantity": 100 }`

---

## Product Variants 🔒

### `GET /products/{productId}/variants`
```json
{ "success": true, "variants": [ { "id": 10, "sku": "GB-S", "price": "499.00", "quantity": 10, "options": { "Size": "S" }, "is_default": true } ] }
```

### `POST /products/{productId}/variants`
```json
{ "sku": "GB-M", "price": 499.00, "quantity": 15, "options": { "Size": "M" }, "is_default": false }
```

### `PUT /products/{productId}/variants/{id}` — full replace
### `DELETE /products/{productId}/variants/{id}` — `{ "success": true }`

---

## Admin Orders 🔒

### `GET /admin/orders?status=&payment_status=&search=&per_page=20`

### `PATCH /admin/orders/{id}/status`
```json
{ "status": "shipped", "comment": "Dispatched via BlueDart" }
```

### `PATCH /admin/orders/{id}/payment-status`
```json
{ "payment_status": "refunded" }
```

### `PATCH /admin/orders/{id}/tracking`
```json
{ "tracking_number": "BD12345", "tracking_url": "https://..." }
```

### `PATCH /admin/orders/{id}/notes`
```json
{ "notes": "Customer requested gift wrap", "admin_notes": "Packed separately" }
```

### `PATCH /admin/orders/{id}/return-status`
```json
{ "return_status": "approved", "return_reason": "Defective product" }
```

---

## Admin Settings 🔒

### `GET /admin/settings`
```json
{ "success": true, "settings": { "id": 1, "name": "My Store", "currency": "INR", "logo_url": null, ... } }
```

### `PUT /admin/settings/{id}`
```json
{ "name": "My Store", "currency": "INR", "email": "...", "phone": "...", "banner_title": "..." }
```

### `POST /admin/settings/{id}/logo` — multipart `logo` field
### `POST /admin/settings/{id}/banner-image` — multipart `banner_image` field

---

## Admin Analytics 🔒

### `GET /admin/analytics/summary`
```json
{
  "success": true,
  "summary": {
    "revenue_30d": 45200.00,
    "orders_30d": 38,
    "customers_30d": 14,
    "aov_30d": 1189.47,
    "revenue_growth_pct": 12.4,
    "daily_revenue": [ { "date": "2026-06-01", "revenue": 1400.00 }, ... ],
    "top_products": [ { "id": 5, "name": "Gold Bangle", "revenue": 9980.00 } ],
    "recent_orders": [ ... ],
    "low_stock_count": 3,
    "out_of_stock_count": 1
  }
}
```

---

## HTTP Status Codes Used

| Code | When |
|---|---|
| 200 | Success |
| 201 | Not used — 200 for creates |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Authenticated but not authorised |
| 404 | Resource not found |
| 422 | Validation error / business rule violation |
| 500 | Unexpected server error |
