# Features

A full-stack e-commerce platform built with **Laravel 11** (API) and **Vue 3 + TypeScript** (frontend).

---

## Table of Contents

- [Storefront](#storefront)
- [Product Catalog](#product-catalog)
- [Cart & Checkout](#cart--checkout)
- [Payments — Razorpay](#payments--razorpay)
- [Shipping](#shipping)
- [Order Management](#order-management)
- [Authentication & OTP](#authentication--otp)
- [Admin Panel](#admin-panel)
- [Notifications](#notifications)
- [Analytics Dashboard](#analytics-dashboard)
- [Developer Tooling](#developer-tooling)
- [Tech Stack](#tech-stack)

---

## Storefront

- Multiple store layouts — **Ladies** and **Grocery** themes with independent routing
- Customisable homepage: banner image, title, tagline, store name
- Category grid on homepage
- New arrivals section (latest 4 products)
- Active & upcoming coupon display with discount labels
- Product listing page with search, A–Z / price sorting
- Out-of-stock overlay on product cards
- Sale badge on discounted products
- Per-store currency symbol pulled from admin settings

---

## Product Catalog

- Products with SKU, price, sale price, short description, full description
- Multiple product images with default image support
- **Product variants** (colour, size, or any custom option)
  - Per-variant SKU, price, sale price, quantity
  - First variant auto-marked as default on creation
  - Variant selector on product detail page (disabled/strikethrough for out-of-stock variants)
- Hierarchical categories with cover images
- Brands
- Tags (many-to-many)
- Inventory tracking — quantity enforced at variant level with fallback to product level
- Stock label: *In Stock / Only N left / Out of Stock*

---

## Cart & Checkout

- Guest cart persisted to `localStorage`; syncs to server on login
- Authenticated cart stored in database
- Stock validation on every cart load (refreshes from live variant quantities)
- Stale error cleared on each load cycle
- Coupon / discount code application with real-time validation
  - Percentage or fixed-amount discounts
  - Minimum order amount rules
  - Usage limits and date ranges
- Shipping rate calculation by postcode + state
  - Shows method name, estimated delivery, and cost
  - Free shipping rules supported
- Order notes field
- Guest checkout without account — account created silently after payment

---

## Payments — Razorpay

- Guest and authenticated payment flows
- Flow: create DB order → OTP verify (guest) → open Razorpay modal → verify signature → confirm order
- HMAC signature verification on callback
- Webhook endpoint for async `payment.captured` events
- Order linked to registered user **after** payment is confirmed (prevents auth errors mid-checkout)
- Payment status: `pending → paid → refunded`

---

## Shipping

- **Multi-provider** architecture (Shiprocket and others configurable)
- Admin can enable / disable / credential providers
- **Shipping zones** — define geographic regions
  - Add locations (states, cities, pincodes) per zone
- **Shipping methods** per zone (Standard, Express, Same-Day, etc.)
- **Rate matrix** — cost rules per method based on:
  - Weight tiers
  - Order amount (flat or percentage)
  - Delivery speed tier
- Free shipping threshold per method
- Delivery time estimates shown at checkout
- Tracking number recorded on orders
- Shipment model for carrier + AWB details

---

## Order Management

### Customer
- Order history page with status and payment badges
- Order detail view with line items and shipping info
- "Order Placed" confirmation modal after guest OTP checkout (with Log In / Go to Store actions)

### Admin
- Paginated, filterable order list (status, payment status, date range, search)
- Update order status: `pending → processing → shipped → delivered → cancelled`
- Update payment status: `unpaid → paid → refunded`
- Add / update tracking number
- Internal admin notes + customer-visible notes
- Return / RMA status management
- Order status history timeline (who changed what and when)

---

## Authentication & OTP

- Email + password registration and login
- Separate **backstore login** endpoint for admin access
- Role-based access control via Spatie Permission (`Customer`, `Admin`)
- **OTP phone verification** — guest checkout flow:
  1. Guest places order → OTP sent to phone (+ email verification link)
  2. Customer enters 6-digit OTP
  3. Account created silently (no auto-login, no cart disruption)
  4. Razorpay payment proceeds as guest
  5. Order linked to account after payment confirmed
- **OTP login** — existing customers can log in by phone
- Email verification via tokenised link
- Set / update password after OTP login
- Sanctum API tokens with Bearer auth
- `phone_verified_at` timestamp recorded on successful OTP

---

## Admin Panel

### Products
- Create, edit, delete products
- Upload multiple images; set default image
- Manage variants (add, edit, delete, mark default)
- Manual stock adjustment

### Categories
- Full CRUD with parent-child hierarchy
- Category cover image upload

### Coupons
- Create percentage or fixed-amount coupons
- Set minimum order amount, max uses, per-user limit, validity dates
- Enable / disable toggle
- Usage log per order

### Customers
- Paginated customer list with search
- Customer detail view (profile, orders)

### Settings
- Store name, tagline, email, phone, address, currency
- Logo upload
- Homepage banner image, title, and text

### Shipping Configuration
- Enable / configure shipping providers
- Create and manage zones + locations
- Create methods and set rate rules

### Notifications
- Configure email (SMTP), SMS (Twilio), WhatsApp channels
- Per-event toggles: order placed, status changed, return updated
- Notification log with delivery status and timestamps

---

## Notifications

| Channel | Events |
|---|---|
| Email | Order placed, status update, return update |
| SMS (Twilio) | Order placed, status update |
| WhatsApp | Order placed, status update |

Logs stored with `channel`, `recipient`, `status`, `sent_at`, and optional error message.

---

## Analytics Dashboard

- **30-day KPIs**: total revenue, orders, customers, average order value
- Growth percentages (vs prior 30-day period)
- Daily revenue chart for the past 30 days
- Top 5 products by revenue
- Recent orders table
- Order status breakdown
- Low-stock alerts (threshold configurable)
- Out-of-stock product count

---

## Developer Tooling

### Artisan Commands

```bash
# Truncate tables interactively or by group / name
php artisan store:flush                        # interactive picker
php artisan store:flush orders                 # group: orders + items + history
php artisan store:flush carts otp              # multiple groups
php artisan store:flush products               # products + variants + images + tags
php artisan store:flush otp_verifications      # single table
php artisan store:flush all --force            # everything, no prompt
```

**Table groups**: `carts`, `orders`, `coupons`, `otp`, `products`

### Frontend Build

```bash
# Skip pre-existing TS errors from unrelated pages
cd frontend && npx vite build
```

### Key Environment Variables

```
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
USE_STATIC_OTP=1          # dev: always accept STATIC_OTP value
STATIC_OTP=123456
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Auth | Laravel Sanctum (Bearer tokens) |
| Roles | Spatie Laravel Permission |
| Frontend | Vue 3, TypeScript, Vite |
| State | Pinia |
| Styling | Tailwind CSS |
| Payments | Razorpay |
| SMS | Twilio |
| Email | Laravel Mail (SMTP) |
| Database | MySQL |
| Dev server | XAMPP / artisan serve + Vite HMR (port 5173) |
