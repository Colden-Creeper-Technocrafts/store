# Backend Production Setup — Apache + PHP-FPM

Replaces `artisan serve` (dev-only) with Apache serving Laravel directly via PHP-FPM.
The API runs on a subdomain (`api.kirva.in`) so the frontend VirtualHost stays clean.

**Assumes:** EC2 instance running, Apache installed, domain DNS pointed at the server.

---

## Architecture

```
Browser
  ├── https://kirva.in        → Apache → /var/www/store/frontend/dist  (Vue SPA)
  └── https://api.kirva.in    → Apache → PHP-FPM → /var/www/store/backend/public
```

---

## 1. Install PHP-FPM

```bash
sudo apt install php8.2-fpm -y
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
```

Enable Apache modules:

```bash
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.2-fpm
```

---

## 2. Create the API VirtualHost

```bash
sudo nano /etc/apache2/sites-available/api.kirva.in.conf
```

```apache
<VirtualHost *:80>
    ServerName api.kirva.in

    DocumentRoot /var/www/store/backend/public

    <Directory /var/www/store/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/api_error.log
    CustomLog ${APACHE_LOG_DIR}/api_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite api.kirva.in.conf
```

---

## 3. Remove Proxy from Frontend VirtualHost

```bash
sudo nano /etc/apache2/sites-available/kirva.in.conf
```

Remove these 3 lines:

```
ProxyPreserveHost On
ProxyPass /api/ http://127.0.0.1:8000/api/
ProxyPassReverse /api/ http://127.0.0.1:8000/api/
```

Save with `Ctrl+O` → Enter → `Ctrl+X`.

Verify the lines are gone:

```bash
grep -i proxy /etc/apache2/sites-available/kirva.in.conf
```

Should return nothing.

---

## 4. Verify Laravel `.htaccess`

```bash
sudo nano /var/www/store/backend/public/.htaccess
```

Must contain:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 5. File Permissions

```bash
sudo chown -R www-data:www-data /var/www/store/backend/storage
sudo chown -R www-data:www-data /var/www/store/backend/bootstrap/cache
sudo chmod -R 775 /var/www/store/backend/storage
sudo chmod -R 775 /var/www/store/backend/bootstrap/cache
```

---

## 6. DNS Record

In Route 53 (or your registrar), add:

| Type | Name | Value |
|---|---|---|
| A | `api.kirva.in` | `13.127.43.4` |

---

## 7. Reload Apache

```bash
sudo apache2ctl configtest      # must say "Syntax OK"
sudo systemctl reload apache2
```

---

## 8. SSL Certificate

```bash
sudo certbot --apache -d api.kirva.in
```

Certbot will auto-update the VirtualHost with HTTPS and set up renewal.

---

## 9. Update Backend `.env`

```bash
nano /var/www/store/backend/.env
```

```
APP_URL=https://api.kirva.in
```

Clear caches:

```bash
cd /var/www/store/backend
php artisan config:clear
php artisan cache:clear
```

---

## 10. Update Frontend API URL

In `frontend/.env.production`:

```
VITE_API_BASE_URL=https://api.kirva.in/api/v1
```

Rebuild and deploy:

```bash
# Local
npx vite build
git add frontend/.env.production frontend/src/services/api.ts
git commit -m "Switch API URL to api.kirva.in"
git push

# EC2
cd /var/www/store
git pull
cd frontend
npx vite build
```

---

## 11. Stop artisan serve

If a systemd service is running it:

```bash
sudo systemctl stop laravel-serve
sudo systemctl disable laravel-serve
```

Otherwise kill by process:

```bash
sudo pkill -f "artisan serve"
```

---

## 12. Verify

```bash
# Should return JSON from Laravel
curl https://api.kirva.in/api/v1/products

# Check PHP-FPM is handling requests
sudo tail -f /var/log/apache2/api_error.log
```

---

## CORS

Ensure `config/cors.php` lists the frontend origin:

```php
'allowed_origins' => ['https://kirva.in'],
```

Then run:

```bash
php artisan config:clear
```
