# Client Delivery & Setup Guide

Step-by-step guide to deploy this project on a fresh client server.  
Complete every section in order — each step depends on the previous one.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Project Files](#2-project-files)
3. [Database](#3-database)
4. [Backend Setup](#4-backend-setup)
5. [Frontend Build](#5-frontend-build)
6. [Web Server (XAMPP / Apache)](#6-web-server-xampp--apache)
7. [First Login & Admin Password](#7-first-login--admin-password)
8. [Store Settings](#8-store-settings)
9. [Razorpay (Payments)](#9-razorpay-payments)
10. [Shipping Setup](#10-shipping-setup)
    - [Option A — Manual (No Integration)](#option-a--manual-no-integration)
    - [Option B — Shiprocket](#option-b--shiprocket)
    - [Option C — Delhivery](#option-c--delhivery)
11. [Email & Notifications](#11-email--notifications)
12. [OTP Configuration](#12-otp-configuration)
13. [Final Checklist](#13-final-checklist)

---

## 1. Prerequisites

Install these on the client machine before anything else.

| Software | Minimum Version | Download |
|---|---|---|
| XAMPP (or LAMP/LEMP) | 8.2+ | apachefriends.org |
| PHP | 8.2 | included in XAMPP |
| MySQL | 8.0 | included in XAMPP |
| Composer | 2.x | getcomposer.org |
| Node.js | 18+ | nodejs.org |

**Verify in terminal:**
```bash
php -v          # must show 8.2+
composer -V
node -v         # must show 18+
npm -v
```

---

## 2. Project Files

Copy the project folder to the web root:

```
C:\xampp\htdocs\store\
├── backend\
└── frontend\
```

> If deploying on Linux: `/var/www/html/store/`

---

## 3. Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Create a new database:
   - **Name:** `store` (or whatever the client prefers)
   - **Collation:** `utf8mb4_unicode_ci`
3. Create a dedicated DB user *(optional but recommended for production)*:
   ```sql
   CREATE USER 'store_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
   GRANT ALL PRIVILEGES ON store.* TO 'store_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

## 4. Backend Setup

All commands run inside the `backend/` folder.

### 4a. Install PHP dependencies
```bash
cd C:\xampp\htdocs\store\backend
composer install --no-dev --optimize-autoloader
```

### 4b. Create environment file
```bash
copy .env.example .env
```

Open `.env` and fill in every section below:

```ini
# ── App ──────────────────────────────────────────────────────────────────
APP_NAME="Client Store Name"          # shown in emails and Razorpay modal
APP_ENV=production
APP_KEY=                              # generated in next step
APP_DEBUG=false
APP_URL=https://api.clientdomain.com  # API subdomain, not the frontend domain

# ── Storefront ────────────────────────────────────────────────────────────
STOREFRONT_NAME="Client Store Name"   # displayed on storefront
STOREFRONT_LAYOUT=ladies              # ladies | grocery

# ── Database ──────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store                     # database name from step 3
DB_USERNAME=root                      # or store_user
DB_PASSWORD=                          # MySQL password

# ── Session ───────────────────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true            # required for HTTPS

# ── Mail (fill in section 11) ─────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=
MAIL_PASSWORD=                        # Gmail App Password (not regular password)
MAIL_FROM_ADDRESS="noreply@clientdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# ── Razorpay (fill in section 9) ──────────────────────────────────────────
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=

# ── OTP / Fast2SMS (fill in section 12) ───────────────────────────────────
USE_STATIC_OTP=0
FAST2SMS_API_KEY=                     # from fast2sms.com dashboard
```

### 4c. Generate app key
```bash
php artisan key:generate
```

### 4d. Run migrations
```bash
php artisan migrate
```

### 4e. Seed the database

**Before seeding — update the admin credentials in the seeder:**

Open `database/seeders/DatabaseSeeder.php` and change:
```php
$admin = User::firstOrCreate(
    ['email' => 'client@example.com'],    // ← client's email
    [
        'name'     => 'Client Name',       // ← client's name
        'password' => 'ChangeMe@123',      // ← temporary password (client changes after first login)
    ]
);
```

Then run:
```bash
php artisan db:seed
```

This seeds:
- Roles (`Admin`, `Vendor`, `Customer`)
- One store settings row for this client (one row per client — do not run `db:seed` twice or it will duplicate)
- Shipping providers (Manual active by default, Shiprocket/Delhivery disabled)
- Shipping zones (Metro Cities, All India) with default rates
- The admin user

### 4f. Link storage (all environments)
```bash
php artisan storage:link
```

### 4g. Set permissions (Linux only)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4h. Cache config for production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Frontend Build

```bash
cd C:\xampp\htdocs\store\frontend
npm install
npx vite build
```

Output goes to `frontend/dist/` — this is what Apache serves.

---

## 6. Web Server (XAMPP / Apache)

### Option A — XAMPP (simplest)

Start Apache and MySQL from XAMPP Control Panel.  
Access the store at `http://localhost/store/frontend/dist/`

For a cleaner URL, add a Virtual Host in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName store.local
    DocumentRoot "C:/xampp/htdocs/store/frontend/dist"

    <Directory "C:/xampp/htdocs/store/frontend/dist">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # API proxy to Laravel
    ProxyPass /api http://localhost:8000/api
    ProxyPassReverse /api http://localhost:8000/api
</VirtualHost>
```

Then start the Laravel API server:
```bash
cd backend
php artisan serve --port=8000
```

### Option B — Production (Linux / cPanel)

Point document root to `frontend/dist/`.  
Point API subdomain (e.g. `api.clientdomain.com`) to `backend/public/`.

`backend/public/.htaccess` is already included and handles routing.

---

## 7. First Login & Admin Password

1. Open `http://localhost/backstore/login` (or the client domain)
2. Log in with the credentials set in `DatabaseSeeder.php`
3. Immediately go to **Profile** → **Change Password** and set a permanent password
4. Keep the login URL `…/backstore/login` private — it is not linked from the storefront

---

## 8. Store Settings

### 8a. Configure store details

Go to **Admin → Settings** and fill in:

| Field | What to Enter |
|---|---|
| Store Name | Client's business name |
| Email | Support / contact email |
| Phone | Support phone number |
| Address | Business address |
| Currency | `INR` (or `USD`, `EUR`, etc.) |
| Banner Title | e.g. "Exclusive Jewellery Collection" |
| Banner Text | Short tagline |

### 8b. Upload logo

1. In **Admin → Settings**, scroll to the Logo section
2. Click **Upload Logo** → select a PNG or JPG (max 3 MB)
3. Click **Save Settings**

### 8c. Upload banner image

1. In **Admin → Settings**, scroll to the Banner section
2. Click **Upload Banner** → select an image (max 6 MB)
3. Click **Save Settings**

### 8d. Set up categories

1. Go to **Admin → Categories → Add Category**
2. Create the top-level categories for the client's product range
3. Add subcategories as needed
4. Products can now be assigned to these categories

### Activate the correct store layout

The seeder creates two stores — `ladies` (id=1) and `grocery` (id=2).  
Only one can be active. To switch layouts, update directly in MySQL:

```sql
-- Activate Ladies layout
UPDATE store_settings SET is_active = 0;
UPDATE store_settings SET is_active = 1 WHERE layout = 'ladies';

-- OR activate Grocery layout
UPDATE store_settings SET is_active = 0;
UPDATE store_settings SET is_active = 1 WHERE layout = 'grocery';
```

---

## 9. Razorpay (Payments)

### Get credentials from Razorpay Dashboard

1. Log in at **dashboard.razorpay.com**
2. Go to **Settings → API Keys**
3. Generate keys (use **Test Mode** first, **Live Mode** when going live)
4. Copy **Key ID** and **Key Secret**

### Add to `.env`
```ini
RAZORPAY_KEY_ID=rzp_test_xxxxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxxxxxx
```

### Set up Webhook (for payment.captured fallback)
1. In Razorpay Dashboard → **Settings → Webhooks** (stay in the same mode — Test or Live)
2. Add URL: `https://api.clientdomain.com/api/v1/payments/razorpay/webhook`
   > Must use `https://` and the **API subdomain** (`api.`), not the frontend domain
3. Select event: `payment.captured`
4. Set a webhook secret and add it to `.env`:
   ```ini
   RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
   ```
5. Run `php artisan config:clear` after updating `.env`

### Switch to Live keys before go-live
```ini
RAZORPAY_KEY_ID=rzp_live_xxxxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxxxxxx
```

> **Test cards:**  
> Card: `5500 6700 0000 1002` · Expiry: any future date · CVV: any 3 digits · OTP: `1234`

---

## 10. Shipping Setup

Go to **Admin → Shipping → Providers** to enable and configure a provider.  
**Manual is already active** — use it until a carrier integration is ready.

---

### Option A — Manual (No Integration)

Already seeded and active. Default rates:
- Metro cities: ₹40 base + ₹15/kg
- All India: ₹60 base + ₹20/kg
- Free shipping on orders ≥ ₹999

To adjust rates: **Admin → Shipping → Methods → Standard Shipping → Rates**

When an order is placed, admin manually enters the AWB and tracking URL from the carrier's dashboard.

---

### Option B — Shiprocket

#### What you need from the client
| Item | Where to get it |
|---|---|
| Shiprocket login email | Client's Shiprocket account |
| Shiprocket login password | Client's Shiprocket account |
| Pickup warehouse pincode | Shiprocket → Settings → Manage Warehouses |
| Pickup location name | Shiprocket → Settings → Manage Warehouses (name field — must match exactly, e.g. `Primary`) |

#### Setup steps
1. **Admin → Shipping → Providers → Shiprocket → Edit**
2. Fill in:
   ```
   Email            → client's Shiprocket email
   Password         → client's Shiprocket password
   Pickup Pincode   → warehouse pincode (e.g. 395010)
   Pickup Location  → warehouse name in Shiprocket (e.g. Primary)
   Default Length   → 10  (cm)
   Default Width    → 10  (cm)
   Default Height   → 10  (cm)
   ```
3. Click **Validate** — it authenticates with Shiprocket and confirms the credentials work
4. Toggle **Active = ON**
5. Turn off Manual provider if you don't want both showing at checkout

#### What Shiprocket provides
- Live courier rates at checkout (multiple carriers: BlueDart, Delhivery via Shiprocket, etc.)
- Automatic AWB assignment after order is placed
- Shipping label generation
- Live tracking from the carrier

#### Important — product weights
Shiprocket rates are weight-based. Set product weights in **Admin → Products → Edit** for accurate quotes. If left blank, the system defaults to `0.1 kg` per item.

---

### Option C — Delhivery

#### What you need from the client
| Item | Where to get it |
|---|---|
| API Token | Delhivery Dashboard → Settings → API |
| Pickup warehouse name | Delhivery Dashboard → Setup → Pickup Locations |
| Pickup warehouse address | Delhivery Dashboard → Setup → Pickup Locations |
| Pickup pincode | Delhivery Dashboard → Setup → Pickup Locations |

#### Setup steps
1. **Admin → Shipping → Providers → Delhivery → Edit**
2. Fill in:
   ```
   API Token        → from Delhivery dashboard
   Pickup Name      → warehouse name in Delhivery
   Pickup Address   → warehouse full address
   Pickup Pincode   → warehouse pincode
   Seller Name      → client's business name
   Shipping Mode    → Surface (or Express)
   Use Staging      → ON while testing, OFF for live
   Default Length   → 10  (cm)
   Default Width    → 10  (cm)
   Default Height   → 10  (cm)
   ```
3. Click **Validate**
4. Toggle **Active = ON**

---

## 11. Email & Notifications

Go to **Admin → Notifications → Settings** to configure channels.

### SMTP (Gmail example)

In `.env`:
```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=client@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx    # Gmail App Password (not regular password)
MAIL_FROM_ADDRESS=client@gmail.com
MAIL_FROM_NAME="Client Store Name"
```

**To get Gmail App Password:**
1. Gmail → Google Account → Security → 2-Step Verification (must be ON)
2. Search "App Passwords" → create one for "Mail"
3. Use the 16-character code as `MAIL_PASSWORD`

### SMS (Fast2SMS)

OTP SMS is sent via Fast2SMS. Add the API key to `.env` (see section 12).  
No admin panel config needed — it reads directly from `.env`.

### Enable notification events

In **Admin → Notifications → Settings**, toggle ON:
- Order Placed (Email + SMS)
- Status Changed (Email)
- Return Updated (Email)

---

## 12. OTP Configuration

OTP is sent to customer's phone during guest checkout.

### Development / Testing
```ini
USE_STATIC_OTP=1
STATIC_OTP=123456
```
Any phone number will accept `123456` as a valid OTP — no SMS is sent.

### Production

Fast2SMS is already integrated. Set these in `.env`:

```ini
USE_STATIC_OTP=0
FAST2SMS_API_KEY=your_api_key_here
```

**To get the API key:**
1. Sign up at **fast2sms.com**
2. Go to **Dev API** in the dashboard
3. Copy the API key

OTP SMS will be sent automatically on guest checkout. Logs appear in `storage/logs/laravel.log` with the label `Fast2SMS OTP sent`.

---

## 13. Final Checklist

Go through every item before handing over to the client.

### Server & Config
- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] `APP_URL` set to the API subdomain (e.g. `https://api.clientdomain.com`)
- [ ] `SESSION_SECURE_COOKIE=true` set (required for HTTPS)
- [ ] `STOREFRONT_NAME` and `STOREFRONT_LAYOUT` set correctly
- [ ] `php artisan storage:link` run
- [ ] `php artisan config:cache` run after any `.env` change
- [ ] File permissions correct (Linux: `storage/` and `bootstrap/cache/` writable)
- [ ] PHP upload limits set (`upload_max_filesize=6M`, `post_max_size=10M` in `php.ini`)

### Database & Data
- [ ] Migrations ran successfully (`php artisan migrate`)
- [ ] Seeder ran (`php artisan db:seed`)
- [ ] Correct store layout is active (`is_active = 1`)
- [ ] Admin email/password changed from default

### Store
- [ ] Store name, logo, banner set in **Admin → Settings**
- [ ] Currency set correctly (INR / USD / etc.)
- [ ] At least one category created
- [ ] Products added with correct prices and weights

### Payments
- [ ] Razorpay Live keys in `.env` (not test keys)
- [ ] Webhook URL registered in Razorpay dashboard
- [ ] Test payment completed successfully end-to-end

### Shipping
- [ ] Active shipping provider configured and validated
- [ ] Shipping rates verified at checkout with a real pincode
- [ ] Product weights set (if using Shiprocket or Delhivery)

### Notifications
- [ ] Email SMTP tested — place a test order and confirm email arrives
- [ ] `FAST2SMS_API_KEY` set in `.env` and `USE_STATIC_OTP=0`
- [ ] Test OTP received on a real phone number

### Security
- [ ] Backstore URL (`/backstore/login`) shared only with admin, not publicly linked
- [ ] Strong admin password set
- [ ] `RAZORPAY_WEBHOOK_SECRET` set


