# Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────┐
│  Browser (Vue 3 SPA)                                    │
│  ├── Pinia stores (auth, cart)                          │
│  ├── src/services/*  (API calls via axios)              │
│  └── src/pages/Ladies | Grocery | Storefront            │
└───────────────────────┬─────────────────────────────────┘
                        │ HTTP/JSON  Bearer token
                        ▼
┌─────────────────────────────────────────────────────────┐
│  Laravel 11 API  (backend/)                             │
│  routes/api/v1.php                                      │
│  ├── Controllers  (thin, delegates to repositories)     │
│  ├── Form Requests  (validation)                        │
│  ├── Repositories  (all DB logic)                       │
│  ├── Services  (OtpService, cross-cutting logic)        │
│  └── Models  (Eloquent)                                 │
└───────────────────────┬─────────────────────────────────┘
                        │ Eloquent / DB::
                        ▼
              ┌─────────────────────┐
              │  MySQL              │
              │  (21 tables)        │
              └─────────────────────┘
                        │
            ┌───────────┼───────────┐
            ▼           ▼           ▼
       Razorpay      Twilio      SMTP Mail
      (payments)     (SMS)       (email)
```

---

## Backend Layer Structure

### 1. Routes — `backend/routes/api/v1.php`

Single versioned file. Two groups:
- **Public** — no middleware (storefront, guest checkout, OTP, Razorpay)
- **Auth** — `auth:sanctum` middleware (cart, orders, admin)

All routes are prefixed `/api/v1/` via `RouteServiceProvider`.

### 2. Controllers — `backend/app/Http/Controllers/Api/V1/`

Responsibilities:
- Parse and validate the request (via Form Request)
- Call one repository method
- Return `response()->json()`

Controllers must NOT contain query logic. If a controller method is more than ~20 lines, move logic to a repository or service.

### 3. Form Requests — `backend/app/Http/Requests/`

All input validation. Laravel's `authorize()` returns `true` (auth is handled by route middleware).

### 4. Repositories — `backend/app/Repositories/`

All database interaction. Each repository implements an interface from `App\Interfaces\`. Bound in `AppServiceProvider`.

| Repository | Owns |
|---|---|
| `AuthRepository` | User create/find/login/logout |
| `CartRepository` | Cart + CartItem CRUD, stock assertion |
| `CategoryRepository` | Category tree per store |
| `CouponRepository` | Coupon lookup, validation, usage recording |
| `CustomerRepository` | Admin customer list |
| `OrderRepository` | Order create, status updates, admin filters |
| `ProductRepository` | Product CRUD with variant sync |
| `ProductImageRepository` | Image upload, default management |
| `ProductVariantRepository` | Variant CRUD, auto-default on first |
| `StorefrontRepository` | Customer-facing product/category queries |

### 5. Services — `backend/app/Services/`

Cross-cutting logic that doesn't belong in a single repository:

- **`OtpService`** — OTP generation, SMS/email dispatch, phone verification, user registration from OTP, order linking

### 6. Models — `backend/app/Models/`

Eloquent models with `$fillable`, `$casts`, relationship methods, and status constants. No business logic in models.

---

## Frontend Layer Structure

```
frontend/src/
├── pages/
│   ├── Ladies/          Six pages for the Ladies store theme
│   ├── Grocery/         Six pages for the Grocery theme
│   └── Storefront/      Six pages for the generic theme
├── stores/
│   ├── auth.ts          Authentication state + token persistence
│   └── cart.ts          Cart items, guest/auth merge, stock refresh
├── services/
│   ├── api.ts           Axios base client with Bearer token header
│   ├── storefront.ts    Products, categories, coupons, formatPrice()
│   ├── orders.ts        Order placement + history
│   ├── payment.ts       Razorpay modal wrapper
│   ├── otp.ts           OTP send/verify
│   ├── coupon.ts        Coupon validation
│   └── admin*.ts        Admin panel API calls
└── components/
    └── store/
        └── CategoryTreeSidebar.vue
```

### State Management (Pinia)

**`auth` store** — `token`, `user`, `role`, persisted to `localStorage`. Exposes `isCustomer`, `isAdmin`, `setAuth()`, `clear()`.

**`cart` store** — Two modes:
- *Guest*: items in `localStorage` as `guestItems`
- *Auth*: items fetched from `/api/v1/cart`

On `load()`: always calls `loadStoreProducts()` to refresh variant stock; clears stale errors.

---

## Multi-Theme Architecture

The same six pages exist in three directories (Ladies, Grocery, Storefront). Each theme has its own router config and visual style. The backend is theme-agnostic — it serves one active store configuration via `store_settings`.

Active store is resolved in every repository call via `StorefrontRepository::resolveActiveStore()`, which reads the first `is_active = true` row in `store_settings`.

---

## Authentication Flow

```
POST /api/v1/login
  → validates credentials
  → creates Sanctum personal access token
  → returns { token, role, user }

Frontend stores token in localStorage via auth store
All subsequent requests: Authorization: Bearer <token>
```

Admin-only endpoints use `hasRole('Admin')` checks inside the controller or middleware.

---

## Payment Flow (Razorpay)

```
1. Frontend: POST /checkout  OR  POST /checkout/guest  → { id: order_id }
2. (Guest only) POST /otp/verify  → account created, no auto-login
3. Frontend: POST /payments/razorpay/create-order  → Razorpay order + key
4. Frontend: open Razorpay modal (JS SDK)
5. User pays in modal
6. Frontend: POST /payments/razorpay/verify  → HMAC validated, order marked paid
7. (Guest only) order linked to registered user in verify()
```

Ownership check: `if ($request->user() !== null && $order->user_id !== null && $order->user_id !== $request->user()->id)` — rejects cross-user access but allows unauthenticated guests.
