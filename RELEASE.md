# Release Notes — v1.0.0

**Release date:** 2026-06-09  
**Branch:** `main`

> For architecture overview see [docs/architecture.md](docs/architecture.md)  
> For full feature list see [FEATURES.md](FEATURES.md)  
> For API reference see [docs/api-contracts.md](docs/api-contracts.md)  
> For database schema see [docs/database.md](docs/database.md)  
> For deployment guides see [docs/delivery.md](docs/delivery.md), [docs/deploy-ec2.md](docs/deploy-ec2.md), [docs/deploy-lightsail.md](docs/deploy-lightsail.md)

---

## Known Limitations

The following features are designed and partially scaffolded but **not yet implemented** in this release:

- **Payment Gateway Config UI** — Razorpay keys are set via `.env`; there is no admin page to rotate them without server access.
- **User & Team Management** — Spatie roles (`Admin`, `Vendor`, `Customer`) exist in the database but there is no admin UI to create/edit/delete users or assign roles.
- **Marketing & Campaigns** — Coupons are supported; scheduled campaigns, email blasts, and loyalty programs are not.
- **Bulk Product Import/Export** — Products must be created individually via the admin UI.
- **SEO Management** — No meta-tag or sitemap management UI.
- **Queue Workers** — Notifications are synchronous. Under production load, configure `QUEUE_CONNECTION=redis` and run `php artisan queue:work` via Supervisor for async delivery.
- **File storage** — Product images use local disk storage. For production with multiple servers, configure `FILESYSTEM_DISK=s3` and set AWS credentials.
- **WhatsApp Production Sender** — WhatsApp notifications default to the Twilio Sandbox number. Register an approved WhatsApp Business sender before going live.

---

## Changelog

### v1.0.0 — 2026-06-09

#### Added

**Storefront**
- Home page with hero, featured categories, and product grid
- Store page with search, category filter, and sort
- Product detail page with image gallery, variant selector, add-to-cart, pincode delivery check
- Shopping cart (persistent, per-user)
- Checkout flow — authenticated and guest checkout, coupon application, shipping method selection
- Razorpay payment integration with signature verification and webhook handler
- Customer registration, login, and profile page with order history
- Saved addresses — auto-saved from orders, default selection at checkout

**Admin Backstore**
- Secure backstore login (`/backstore/login`) — Admin role required
- **Analytics Dashboard** — real-time KPIs (revenue, orders, customers, AOV), daily revenue bar chart, recent orders table, top products, low-stock alerts
- **Category Management** — hierarchical tree CRUD
- **Product Management** — full CRUD, multi-image upload, product variants, inline inventory adjustment, stock badge and low-stock filter
- **Coupon / Offers** — percentage and flat-amount coupons with minimum order, usage limits, and expiry
- **Order Management** — paginated list with search/filter, order detail slide-over, full status workflow, tracking number/URL entry, admin notes, return request management
- **Fulfillment** — auto-fulfill via Shiprocket (AWB assignment, label URL) or manual tracking entry; live tracking events panel
- **Returns** — approve, reject, refund return requests with reason capture
- **Shipping** — provider credentials (Shiprocket, Delhivery, Manual), zone/location management, methods, weight-based rate tiers, pincode serviceability check
- **Customer Management** — customer list with search, slide-over with full order history
- **Store Settings** — store name, contact info, currency, logo, banner

**Notifications**
- HTML email templates for order placed, status changed, return updated
- SMS via Twilio REST API with Indian phone number normalisation
- WhatsApp via Twilio WhatsApp API
- Notification log with channel, event, recipient, status, error
- Admin UI — per-event toggles, Twilio credential input, log table with filters

**Infrastructure**
- Laravel Sanctum token authentication
- Spatie Laravel Permission — Admin, Vendor, Customer roles
- Repository pattern with interface bindings
- Form Request validation throughout
- Database seeder — store settings, shipping zones/rates, roles, admin user
