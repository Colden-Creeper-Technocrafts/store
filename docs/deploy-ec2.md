# AWS EC2 Setup Guide

A step-by-step guide to deploying this application on AWS EC2 with Ubuntu and Apache2.

---

## Prerequisites

- AWS account ([aws.amazon.com](https://aws.amazon.com))
- Domain name (optional but recommended)
- Basic familiarity with SSH and Linux

---

## 1. Launch an EC2 Instance

1. Log in to the [AWS EC2 Console](https://console.aws.amazon.com/ec2)
2. Click **Launch instance**
3. Enter a name (e.g., `store-production`)
4. Under **Application and OS Images**, select:
   - **Ubuntu Server 22.04 LTS (HVM), SSD Volume Type**
5. Under **Instance type**, select:
   - **t3.small** (2 GB RAM, 2 vCPU) — recommended minimum for production
   - **t3.micro** (1 GB RAM) — suitable for low-traffic or staging (Free Tier eligible)
6. Under **Key pair**, click **Create new key pair**:
   - Name it (e.g., `store-key`)
   - Type: **RSA**, Format: **.pem**
   - Download and save the `.pem` file securely
7. Under **Network settings**, click **Edit** and add inbound rules (see Section 2)
8. Under **Configure storage**, set at least **20 GB** gp3
9. Click **Launch instance**

---

## 2. Configure Security Group / Open Ports

In the **Network settings** during launch (or via EC2 → Security Groups afterward), add the following inbound rules:

| Type         | Protocol | Port Range | Source    |
|--------------|----------|------------|-----------|
| SSH          | TCP      | 22         | My IP     |
| HTTP         | TCP      | 80         | 0.0.0.0/0 |
| HTTPS        | TCP      | 443        | 0.0.0.0/0 |
| Custom TCP   | TCP      | 3000       | 0.0.0.0/0 | *(backend API, can remove after Apache setup)* |

---

## 3. Attach an Elastic IP

1. In the EC2 console, go to **Network & Security → Elastic IPs**
2. Click **Allocate Elastic IP address** → **Allocate**
3. Select the new IP → **Actions → Associate Elastic IP address**
4. Associate it with your instance
5. Note the Elastic IP — you'll use it for DNS

---

## 4. Connect to Your Instance

```bash
# Set correct permissions on the key file
chmod 400 ~/Downloads/store-key.pem

# Connect via SSH
ssh -i ~/Downloads/store-key.pem ubuntu@<YOUR_ELASTIC_IP>
```

On Windows (PowerShell):

```powershell
ssh -i "C:\Users\<you>\Downloads\store-key.pem" ubuntu@<YOUR_ELASTIC_IP>
```

---

## 5. Install Dependencies

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Add Ondrej PHP PPA and install PHP 8.2 + extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
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
CREATE DATABASE kirva_commerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kirva_commerce'@'localhost' IDENTIFIED BY 'KIrvA#5617j';
GRANT ALL PRIVILEGES ON kirva_commerce.* TO 'kirva_commerce'@'localhost';
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

Paste the following (replace `yourdomain.com` with your actual domain or Elastic IP):

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

# Obtain certificate (requires a domain pointing to your Elastic IP)
sudo certbot --apache -d kirva.in -d www.kirva.in

# Certbot will auto-modify your Apache config for HTTPS
# Certificates auto-renew via a systemd timer — verify with:
sudo systemctl status certbot.timer
```

---

## 11. Attach a Domain

### Using Route 53:
1. Go to [Route 53 Console](https://console.aws.amazon.com/route53) → **Hosted zones** → **Create hosted zone**
2. Enter your domain name and click **Create**
3. Create the following records:

| Record type | Name | Value           |
|-------------|------|-----------------|
| A           | @    | `<ELASTIC_IP>`  |
| A           | www  | `<ELASTIC_IP>`  |

4. Copy the Route 53 nameservers and set them at your domain registrar

### Using an external DNS provider:
Simply point an **A record** at your Elastic IP directly in your registrar's DNS settings.

---

## 12. Set Up Automated Backups

### EC2 Snapshots (via AWS Backup or Data Lifecycle Manager):
1. In EC2 console → **Elastic Block Store → Snapshots** → **Create snapshot**
2. Or go to **AWS Backup** to configure automated daily snapshots with retention policies

### Database backups:

```bash
# Create a backup script
nano /home/ubuntu/backup_db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u kirva_store -p'StrongPassword123!' kirva_store | gzip > /home/ubuntu/backups/db_$DATE.sql.gz
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
DB_DATABASE=kirva_store
DB_USERNAME=kirva_store
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
| SSL certificate error | Ensure port 80 is open in Security Group and DNS has propagated |
| `php artisan` commands fail | Check `.env` is configured and `composer install` was run |
| SSH connection refused | Check Security Group inbound rule allows port 22 from your IP |
| Cannot connect after reboot | Elastic IP may have been released — verify it's still associated |
| Logo / banner upload fails (422) | PHP upload limits too low — see below |

### PHP Upload Limits

Check and update `/etc/php/8.2/fpm/php.ini`:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Set these two values:

```ini
upload_max_filesize = 6M
post_max_size = 10M
```

Then restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl reload apache2
```

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

# Check instance metadata (from within EC2)
curl http://169.254.169.254/latest/meta-data/instance-type
```

---

## Estimated Monthly Cost (ap-south-1 / Mumbai region)

| Instance Type | RAM  | vCPU | Price/mo (approx) |
|---------------|------|------|-------------------|
| t3.micro      | 1 GB | 2    | ~$8               |
| t3.small      | 2 GB | 2    | ~$16              |
| t3.medium     | 4 GB | 2    | ~$32              |

> Recommended minimum for production: **t3.small**

Additional costs: Elastic IP (~$3.60/mo if unattached), EBS storage (~$0.08/GB/mo), data transfer, Route 53 (~$0.50/zone/mo).


