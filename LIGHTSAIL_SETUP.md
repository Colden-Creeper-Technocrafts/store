# Amazon Lightsail Setup Guide

A step-by-step guide to deploying this application on Amazon Lightsail.

---

## Prerequisites

- AWS account ([aws.amazon.com](https://aws.amazon.com))
- Domain name (optional but recommended)
- Basic familiarity with SSH and Linux

---

## 1. Create a Lightsail Instance

1. Log in to the [AWS Lightsail Console](https://lightsail.aws.amazon.com)
2. Click **Create instance**
3. Select a region close to your users
4. Under **Select a blueprint**, choose:
   - Platform: **Linux/Unix**
   - Blueprint: **OS Only → Ubuntu 22.04 LTS**
5. Under **Choose your instance plan**, select at minimum:
   - **$10/month** (2 GB RAM, 1 vCPU, 60 GB SSD) — recommended for production
   - **$5/month** (1 GB RAM) — suitable for low-traffic or staging
6. Give your instance a name (e.g., `store-production`)
7. Click **Create instance**

---

## 2. Configure Firewall / Open Ports

1. In the Lightsail console, go to your instance → **Networking** tab
2. Under **IPv4 Firewall**, add the following rules:

| Application | Protocol | Port Range |
|-------------|----------|------------|
| SSH         | TCP      | 22         |
| HTTP        | TCP      | 80         |
| HTTPS       | TCP      | 443        |
| Custom      | TCP      | 3000       | *(backend API, can remove after Apache setup)* |

3. Click **Save**

---

## 3. Attach a Static IP

1. In the Lightsail console, go to **Networking → Static IPs**
2. Click **Create static IP**
3. Attach it to your instance
4. Note the static IP address — you'll use it for DNS

---

## 4. Connect to Your Instance

```bash
# Download the SSH key from Lightsail console (Account → SSH keys)
chmod 400 ~/Downloads/LightsailDefaultKey.pem

ssh -i ~/Downloads/LightsailDefaultKey.pem ubuntu@<YOUR_STATIC_IP>
```

Or use the **Connect using SSH** browser button in the Lightsail console.

---

## 5. Install Dependencies

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PHP 8.2 + extensions (for backend)
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install MySQL
sudo apt install -y mysql-server

# Install Apache
sudo apt install -y apache2

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PM2 (process manager for Node.js)
sudo npm install -g pm2
```

---

## 6. Configure MySQL

```bash
sudo mysql_secure_installation

# Log in and create the database
sudo mysql -u root -p

# Inside MySQL:
CREATE DATABASE kirva_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kirva_store'@'localhost' IDENTIFIED BY 'Kirva@28062023';
GRANT ALL PRIVILEGES ON store_db.* TO 'kirva_store'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 7. Deploy the Application

```bash
# Create app directory
sudo mkdir -p /var/www/store
sudo chown ubuntu:ubuntu /var/www/store

# Clone the repository
cd /var/www/store
git clone https://github.com/<your-org>/store.git .

# --- Backend setup ---
cd /var/www/store/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials and app URL
nano .env

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

# --- Frontend setup ---
cd /var/www/store/frontend
npm install
npm run build
```

---

## 8. Configure Apache

Enable required Apache modules:

```bash
sudo a2enmod proxy proxy_http rewrite headers
```

Create a new Apache virtual host config:

```bash
sudo nano /etc/apache2/sites-available/store.conf
```

Paste the following (replace `yourdomain.com` with your actual domain or static IP):

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    # Frontend (React/Vite static build)
    DocumentRoot /var/www/store/frontend/dist

    <Directory /var/www/store/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        FallbackResource /index.html
    </Directory>

    # Backend API (Laravel) — proxy to artisan serve
    ProxyPreserveHost On
    ProxyPass /api/ http://127.0.0.1:8000/api/
    ProxyPassReverse /api/ http://127.0.0.1:8000/api/

    ErrorLog ${APACHE_LOG_DIR}/store_error.log
    CustomLog ${APACHE_LOG_DIR}/store_access.log combined
</VirtualHost>
```

```bash
# Enable the site and disable the default
sudo a2ensite store.conf
sudo a2dissite 000-default.conf

# Test config and reload
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## 9. Start the Backend (Laravel)

```bash
cd /var/www/store/backend

# Start with PM2
pm2 start "php artisan serve --host=127.0.0.1 --port=8000" --name store-backend

# Save PM2 process list and configure startup
pm2 save
pm2 startup
# Run the command output by pm2 startup
```

---

## 10. Set Up SSL with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain certificate (requires a domain pointing to your static IP)
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Certbot will auto-modify your Apache config for HTTPS
# Certificates auto-renew via a systemd timer — verify with:
sudo systemctl status certbot.timer
```

---

## 11. Attach a Domain (Route 53 or External DNS)

### Using Lightsail DNS:
1. In Lightsail console → **Networking → DNS zones** → **Create DNS zone**
2. Add your domain and create the following records:

| Record type | Name | Value          |
|-------------|------|----------------|
| A           | @    | `<STATIC_IP>`  |
| A           | www  | `<STATIC_IP>`  |

3. Copy the Lightsail nameservers and set them at your domain registrar

---

## 12. Set Up Automated Backups

1. In Lightsail console → your instance → **Snapshots** tab
2. Enable **Automatic snapshots** (daily, 7-day retention)

For database backups:

```bash
# Create a backup script
nano /home/ubuntu/backup_db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u store_user -p'StrongPassword123!' store_db | gzip > /home/ubuntu/backups/db_$DATE.sql.gz
# Keep only last 7 days
find /home/ubuntu/backups -name "db_*.sql.gz" -mtime +7 -delete
```

```bash
chmod +x /home/ubuntu/backup_db.sh
mkdir -p /home/ubuntu/backups

# Schedule via cron (runs daily at 2 AM)
crontab -e
# Add: 0 2 * * * /home/ubuntu/backup_db.sh
```

---

## 13. Environment Variables Reference

Key variables to set in `/var/www/store/backend/.env`:

```env
APP_NAME="Store"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_db
DB_USERNAME=store_user
DB_PASSWORD=StrongPassword123!

MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=<SES_SMTP_USER>
MAIL_PASSWORD=<SES_SMTP_PASSWORD>
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Apache 502 Bad Gateway | Check if backend is running: `pm2 status` |
| Permission denied on storage | `sudo chown -R www-data:www-data /var/www/store/backend/storage` |
| MySQL connection refused | `sudo systemctl start mysql` |
| SSL certificate error | Ensure port 80 is open and domain DNS has propagated |
| `php artisan` commands fail | Check `.env` is configured and `composer install` was run |

---

## Useful Commands

```bash
# Check Apache logs
sudo tail -f /var/log/apache2/store_error.log

# Check backend process
pm2 logs store-backend

# Restart backend
pm2 restart store-backend

# Reload Apache after config changes
sudo systemctl reload apache2

# Check disk usage
df -h

# Check memory usage
free -m
```

---

## Estimated Monthly Cost

| Plan       | RAM  | vCPU | SSD  | Transfer | Price/mo |
|------------|------|------|------|----------|----------|
| Nano       | 512MB| 1    | 20GB | 1TB      | $3.50    |
| Micro      | 1GB  | 1    | 40GB | 2TB      | $5.00    |
| Small      | 2GB  | 1    | 60GB | 3TB      | $10.00   |
| Medium     | 4GB  | 2    | 80GB | 4TB      | $20.00   |

> Recommended minimum for production: **Small ($10/mo)**

Static IP, DNS zones, and snapshots are included free with a running instance.
