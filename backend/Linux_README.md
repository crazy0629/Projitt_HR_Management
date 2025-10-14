
# Laravel Project - Local Setup on Linux

This guide provides step-by-step instructions to set up and run a Laravel project on a Linux system.

---

## 🛠 Prerequisites

Ensure the following packages are installed:

- PHP >= 8.1 and extensions: `php-mbstring`, `php-xml`, `php-bcmath`, `php-curl`, `php-mysql`, `php-zip`, `php-cli`, `php-common`
- Composer
- MySQL or PostgreSQL
- Node.js and npm (for frontend assets, if needed)
- Git

---

## 📥 Step 1: Clone the Repository

```bash
cd ~/your-project-directory
git clone https://github.com/your-username/your-laravel-project.git
cd your-laravel-project
```

---

## 📦 Step 2: Install PHP Dependencies

```bash
composer install
```

---

## 📁 Step 3: Create Environment File

```bash
cp .env.example .env
```

Update your `.env` file with the correct database and app configurations.

---

## 🔑 Step 4: Generate Application Key

```bash
php artisan key:generate
```

---

## 🧪 Step 5: Run Migrations (and Seeders if needed)

```bash
php artisan migrate
# Optional: php artisan db:seed
```

---

## ⚙️ Step 6: Set Permissions

```bash
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
```

---

## 🚀 Step 7: Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

---

## 🧾 Optional: Install Frontend Dependencies

If your project includes frontend assets:

```bash
npm install
npm run dev
```

---

## 🧹 Common Issues

- If `.env` changes don't reflect:
  ```bash
  php artisan config:clear
  ```

- If file permission issues occur:
  ```bash
  sudo chown -R $USER:www-data .
  ```

---

## ✅ Done!

Your Laravel project is now up and running locally on Linux 🚀
