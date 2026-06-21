# Database

Engine: **MySQL**. All monetary columns are `DECIMAL(10,2)` in the base currency unit.

---

## Table Reference

### `store_settings`
Central store configuration. One row per store; `is_active = 1` is the active store.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar | Store display name |
| `slug` | varchar | URL-safe identifier |
| `email` | varchar nullable | |
| `phone` | varchar nullable | |
| `address` | text nullable | |
| `currency` | varchar(3) | ISO 4217 — `INR`, `USD`, etc. |
| `logo_url` | varchar nullable | Uploaded via admin |
| `banner_image_url` | varchar nullable | Homepage banner |
| `banner_title` | varchar nullable | |
| `banner_text` | text nullable | |
| `is_active` | tinyint(1) | Only one active store at a time |
| `notification_config` | json nullable | Email/SMS/WhatsApp settings |

---

### `categories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `store_setting_id` | bigint FK → store_settings | |
| `parent_id` | bigint FK → categories nullable | Self-referential tree |
| `name` | varchar | |
| `slug` | varchar unique | |
| `image` | varchar nullable | |
| `sort_order` | int default 0 | |

---

### `products`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category_id` | bigint FK → categories nullable | |
| `name` | varchar | |
| `slug` | varchar unique | |
| `sku` | varchar nullable | Mirrors default variant SKU |
| `short_description` | varchar nullable | |
| `description` | text nullable | HTML allowed |
| `price` | decimal(10,2) | Mirrors default variant price |
| `sale_price` | decimal(10,2) nullable | |
| `quantity` | int default 0 | Mirrors default variant quantity |
| `weight` | decimal(8,2) nullable | kg |
| `length` / `width` / `height` | decimal nullable | cm |
| `status` | tinyint(1) default 1 | 1 = active |
| `featured` | tinyint(1) default 0 | |

> **Note**: `products.price`, `products.quantity`, `products.sku` mirror the default variant and are kept in sync by `ProductRepository`. Always read variant-level values for pricing and stock; the product columns are denormalised copies for performance.

---

### `product_variants`

Source of truth for price, stock, and SKU.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_id` | bigint FK → products | |
| `sku` | varchar nullable | |
| `price` | decimal(10,2) | |
| `sale_price` | decimal(10,2) nullable | |
| `quantity` | int default 0 | |
| `weight` | decimal nullable | |
| `options` | json nullable | `{"Color":"Red","Size":"M"}` |
| `is_default` | tinyint(1) default 0 | Exactly one per product = 1 |
| `status` | tinyint(1) default 1 | |

**Default variant selection** (always use this pattern):
```sql
SELECT id FROM product_variants
WHERE product_id = ?
ORDER BY is_default DESC, id ASC
LIMIT 1
```

---

### `product_images`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_id` | bigint FK → products | |
| `url` | varchar | |
| `alt` | varchar nullable | |
| `sort_order` | int default 0 | |
| `is_default` | tinyint(1) default 0 | |

---

### `product_variant_value`
Pivot: variant ↔ variant attribute value.

| Column | Type |
|---|---|
| `product_variant_id` | bigint FK → product_variants |
| `variant_value_id` | bigint FK → variant_values |

---

### `product_tag`
Pivot: product ↔ tag.

| Column | Type |
|---|---|
| `product_id` | bigint |
| `tag_id` | bigint |

---

### `carts`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | bigint FK → users | One cart per user |
| `created_at` / `updated_at` | timestamp | |

---

### `cart_items`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `cart_id` | bigint FK → carts | |
| `product_id` | bigint FK → products | |
| `quantity` | int | |

---

### `orders`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | bigint FK → users **nullable** | null = guest until payment confirmed |
| `status` | enum | `pending/processing/shipped/delivered/cancelled` |
| `payment_status` | enum | `pending/paid/failed/refunded` |
| `payment_gateway` | varchar nullable | `razorpay` |
| `payment_id` | varchar nullable | Razorpay payment ID |
| `razorpay_order_id` | varchar nullable | |
| `subtotal` | decimal(10,2) | Before discount |
| `discount_amount` | decimal(10,2) default 0 | |
| `shipping_cost` | decimal(10,2) default 0 | |
| `total` | decimal(10,2) | Final amount charged |
| `coupon_id` | bigint FK → coupons nullable | |
| `coupon_code` | varchar nullable | Snapshot at order time |
| `shipping_method_id` | bigint FK → shipping_methods nullable | |
| `shipping_name` | varchar | |
| `shipping_email` | varchar | |
| `shipping_phone` | varchar nullable | |
| `shipping_address` | varchar | |
| `shipping_city` | varchar | |
| `shipping_state` | varchar nullable | |
| `shipping_postal_code` | varchar | |
| `shipping_country` | varchar | |
| `notes` | text nullable | Customer notes |
| `admin_notes` | text nullable | Internal only |
| `tracking_number` | varchar nullable | |
| `tracking_url` | varchar nullable | |
| `return_status` | enum nullable | `requested/approved/rejected/refunded` |
| `return_reason` | text nullable | |

---

### `order_items`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `order_id` | bigint FK → orders | |
| `product_id` | bigint FK → products | |
| `name` | varchar | Snapshot of product name |
| `sku` | varchar nullable | Snapshot of variant SKU |
| `price` | decimal(10,2) | Effective price at order time |
| `quantity` | int | |
| `line_total` | decimal(10,2) | `price × quantity` |
| `weight` | decimal nullable | |

---

### `order_status_histories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `order_id` | bigint FK → orders | |
| `status` | varchar | New status value |
| `comment` | text nullable | |
| `changed_by` | bigint nullable | user_id of admin |
| `created_at` | timestamp | |

---

### `coupons`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `store_setting_id` | bigint FK → store_settings | |
| `code` | varchar unique | |
| `description` | text nullable | |
| `discount_type` | enum | `percentage` / `fixed` |
| `discount_value` | decimal(10,2) | % or currency amount |
| `min_order_amount` | decimal(10,2) nullable | |
| `max_uses` | int nullable | null = unlimited |
| `uses_per_user` | int nullable | |
| `starts_at` | timestamp nullable | |
| `expires_at` | timestamp nullable | |
| `is_active` | tinyint(1) default 1 | |

---

### `coupon_usages`

| Column | Type |
|---|---|
| `id` | bigint PK |
| `coupon_id` | bigint FK → coupons |
| `order_id` | bigint FK → orders |
| `user_id` | bigint FK → users nullable |
| `created_at` | timestamp |

---

### `otp_verifications`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `phone` | varchar | |
| `email` | varchar nullable | |
| `otp` | varchar(6) | 6-digit code |
| `email_token` | varchar(64) nullable | For email verification link |
| `order_id` | bigint FK → orders nullable | Links OTP to order for post-payment user linking |
| `expires_at` | timestamp | OTP_TTL = 10 minutes |
| `verified_at` | timestamp nullable | Set on successful OTP entry |
| `email_verified_at` | timestamp nullable | Set on email link click |

---

### `users`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar | |
| `email` | varchar unique nullable | Nullable for phone-only accounts |
| `phone` | varchar unique nullable | |
| `password` | varchar nullable | Null for OTP-only accounts |
| `phone_verified_at` | timestamp nullable | |
| `email_verified_at` | timestamp nullable | |
| `remember_token` | varchar nullable | |

---

### Shipping Tables

```
shipping_providers          — Shiprocket, custom, etc.
shipping_zones              — Geographic regions
shipping_zone_locations     — States/cities/pincodes within a zone
shipping_methods            — Standard, Express, Same-Day (belong to zone)
shipping_rates              — Cost rules per method (weight/amount tiers)
shipments                   — Carrier + AWB + tracking per order
```

---

### `notification_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `channel` | varchar | `email`, `sms`, `whatsapp` |
| `recipient` | varchar | Phone or email address |
| `event` | varchar | `order_placed`, `status_changed`, etc. |
| `status` | varchar | `sent`, `failed` |
| `error` | text nullable | |
| `sent_at` | timestamp nullable | |

---

## Key Relationships

```
store_settings
  └── categories (1:N)
        └── products (1:N)
              ├── product_variants (1:N) ← source of truth for price/stock
              ├── product_images (1:N)
              └── product_tag (pivot)

users
  ├── carts (1:1) → cart_items → products
  └── orders (1:N) → order_items → products
                   → order_status_histories
                   → shipment (1:1)
                   → coupon (N:1)

otp_verifications
  └── order_id FK → orders  (used to link order to user after payment)
```

---

## Migration Naming Convention

```
YYYY_MM_DD_HHMMSS_<action>_<table>.php
```

Data-repair migrations (not schema changes) are named `fix_*`. Always idempotent — safe to re-run.
