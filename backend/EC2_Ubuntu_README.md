# 🚀 Deployment on AWS

This guide documents the setup process to deploy this Laravel project on an AWS EC2 Ubuntu server using PHP 8.2, Composer, Git, and Nginx.

---

## 📁 Project Path

The project is located at:
```
/var/www/backend/
```

---

## ✅ Prerequisites

- AWS EC2 instance (Ubuntu 22.04)
- SSH access to server
- Laravel project hosted on GitHub
- MySQL or RDS database
- Domain (optional, for SSL)

---

## 🛠️ Deployment Steps

### 1. SSH into EC2

```bash
ssh -i your-key.pem ubuntu@your-ec2-ip
```

---

### 2. Update System and Install PHP 8.2

```bash
sudo apt update && sudo apt upgrade -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php8.2 php8.2-cli php8.2-mbstring php8.2-xml \
php8.2-bcmath php8.2-curl php8.2-mysql php8.2-zip php8.2-common unzip
```

---

### 3. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

### 4. Install Git

```bash
sudo apt install git -y
```

---

### 5. Clone Laravel Project

```bash
cd /var/www/
sudo git clone https://github.com/your-username/your-laravel-repo.git backend
cd backend
```

---

### 6. Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/backend
sudo chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache
```

---

### 7. Install Laravel Dependencies

```bash
composer install
cp .env.example .env
php artisan key:generate
```

> 📌 Update `.env` with database credentials and app config.

---

### 8. Install and Configure Nginx

```bash
sudo apt install nginx -y
```

Create Nginx config:

```bash
sudo nano /etc/nginx/sites-available/backend
```

Paste the following:

```nginx
server {
    listen 80;
    server_name your_domain_or_ip;

    root /var/www/backend/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable site and restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/backend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### 9. Laravel Optimization

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 10. Final Permissions

```bash
sudo chown -R www-data:www-data /var/www/backend
```

---

## 🔒 Optional: SSL with Let’s Encrypt

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx
```

Follow prompts to install SSL for your domain.

---

## ✅ Done!

Your Laravel app should now be live at:
```
http://your_domain_or_ip
```

---

## 📌 Tips

- Configure `.env` carefully (DB, APP_URL, MAIL, etc.)
- Restart `php8.2-fpm` if needed: `sudo systemctl restart php8.2-fpm`
- Use `queue:work` and `Supervisor` if you use queues

---
