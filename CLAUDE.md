# CLAUDE.md

Project guide for AI-assisted development. Read this before touching any file.

---

## Project Architecture

Full-stack e-commerce platform. Two separate apps in one repo.

```
store/
├── backend/    Laravel 11 — JSON API only, no Blade views
├── frontend/   Vue 3 + TypeScript — SPA served by Vite
└── docs/       Architecture, database, API contracts
```

**Backend base URL**: `http://localhost:8000/api/v1/`  
**Frontend dev server**: `http://localhost:5173`  
**Frontend build output**: `frontend/dist/` (served by Apache/Nginx in production)

See [docs/architecture.md](docs/architecture.md) for the full layer diagram.

---

## Coding Standards

### Backend (Laravel 11 / PHP 8.2+)

**Repository pattern — always.**  
Controllers are thin. Business logic and all DB queries live in repositories.

```
App\Interfaces\FooRepositoryInterface   ← contract
App\Repositories\FooRepository          ← implementation
App\Http\Controllers\Api\V1\FooController ← calls interface, returns JSON
```

Controllers receive the interface via constructor injection with `readonly`:
```php
public function __construct(private readonly FooRepositoryInterface $foos) {}
```

Bindings are registered in `app/Providers/AppServiceProvider.php`.

**Form Requests for all input validation.** Never validate inside a controller method directly. Put rules in `App\Http\Requests\*Request`.

**Transactions for any multi-step write:**
```php
DB::transaction(function () use ($payload) { ... });
```

**Stock operations must use `lockForUpdate()`** to prevent race conditions.

**Monetary values are stored as `DECIMAL(10,2)` in the base currency unit** (rupees, not paise). Never store paise in the database. Convert to paise only when calling Razorpay (`$amount * 100`).

**COALESCE pattern for variant-first pricing:**
```php
COALESCE(pv.sale_price, pv.price, products.sale_price, products.price)
```
Variant values always take priority over product-level values.

**Default variant selection** — always use a correlated subquery, never a simple JOIN filter:
```sql
(SELECT id FROM product_variants WHERE product_id = products.id
 ORDER BY is_default DESC, id ASC LIMIT 1)
```

### Frontend (Vue 3 + TypeScript)

- `<script setup lang="ts">` — always use Composition API
- State: **Pinia stores** in `src/stores/`
- HTTP: **`src/services/api.ts`** base client — all service files wrap this
- Currency: use `formatPrice()` from `src/services/storefront.ts` everywhere — never hardcode `₹` or `$`
- Build: `npx vite build` (not `npm run build` — skips pre-existing TS errors in unrelated pages)

---

## Database Conventions

See [docs/database.md](docs/database.md) for full schema.

| Convention | Rule |
|---|---|
| Monetary columns | `DECIMAL(10,2)`, always base currency unit |
| Boolean status | `status` (products) — `tinyint(1)` |
| String enums | `status` (orders): `pending/processing/shipped/delivered/cancelled` |
| Payment status | `pending/paid/failed/refunded` |
| Return status | `requested/approved/rejected/refunded` |
| Nullable FK | `orders.user_id` is nullable (guest orders) |
| Default variant | `product_variants.is_default = 1` — exactly one per product |
| Timestamps | All models use `created_at` / `updated_at` (automatic) |
| Verification | `verified_at`, `email_verified_at`, `phone_verified_at` for OTP tracking |

---

## API Response Format

See [docs/api-contracts.md](docs/api-contracts.md) for all endpoints.

Every response includes a `success` boolean at the root.

**Success — single resource:**
```json
{ "success": true, "product": { ... } }
```

**Success — collection with pagination:**
```json
{
  "success": true,
  "products": [ ... ],
  "meta": { "total": 120, "per_page": 20, "current_page": 1, "last_page": 6 }
}
```

**Success — action (no body):**
```json
{ "success": true }
```

**Success — auth:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "...",
  "role": "Customer",
  "user": { "id": 1, "name": "...", "email": "...", "role": "Customer" }
}
```

**Error — not found / forbidden:**
```json
{ "success": false, "message": "Order not found" }
```
HTTP status codes: `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Unprocessable`, `500 Server Error`

**Validation error (422):**
```json
{
  "message": "The name field is required.",
  "errors": { "name": ["The name field is required."] }
}
```
Laravel's default validation response shape — do not wrap this in `success`.

---

## Key Roles

| Role | Access |
|---|---|
| `Admin` | All authenticated routes including `/admin/*` |
| `Customer` | Cart, orders, profile |

Role assignment via Spatie Permission. Assigned in `OtpService` / `AuthRepository` on user creation.  
Backstore login (`POST /api/v1/backstore/login`) enforces `hasRole('Admin')` — returns 403 otherwise.

---

## OTP / Guest Checkout Flow

1. Guest adds to cart (localStorage) → fills checkout form
2. `POST /checkout/guest` → creates `Order` with `user_id = null`
3. `POST /otp/send` → sends 6-digit code to phone, logs to `otp_verifications`
4. `POST /otp/verify` → validates code, registers user (`phone_verified_at` set), returns `{success, is_new_user}` — **no token issued**
5. `POST /payments/razorpay/create-order` → order still has `user_id = null`, passes ownership check
6. Razorpay modal opens → user pays
7. `POST /payments/razorpay/verify` → validates HMAC, marks order paid, then links `order.user_id` from OTP record
8. Frontend shows "Order Placed" modal with "Log In" / "Go to Store" buttons

**Critical**: do not re-introduce `setAuth()` / auto-login inside `OtpController::verify()`. The deferred linking in step 7 is intentional.

---

## Dev Commands

```bash
# Backend
cd backend
php artisan serve            # API on :8000
php artisan store:flush      # interactive table truncator
php artisan migrate

# Frontend
cd frontend
npm run dev                  # Vite HMR on :5173
npx vite build               # production build (skips pre-existing TS errors)
```

Static OTP for local dev — set in `.env`:
```
USE_STATIC_OTP=1
STATIC_OTP=123456
```
