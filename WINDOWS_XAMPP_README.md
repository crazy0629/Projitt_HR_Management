
# Laravel Project - Local Setup on Windows (XAMPP)

This guide provides step-by-step instructions to set up and run a Laravel project on a Windows machine using XAMPP.

---

## 🛠 Prerequisites

- XAMPP (PHP >= 8.1)
- Composer
- Git
- Node.js and npm (optional, for frontend assets)

---

## 📁 Step 1: Install and Configure XAMPP

1. Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Start **Apache** and **MySQL** from the XAMPP Control Panel
3. Create a new database using phpMyAdmin (e.g., `laravel_app`)

---

## 📥 Step 2: Clone the Repository

```bash
cd C:\xampp\htdocs
git clone https://github.com/your-username/your-laravel-project.git
cd your-laravel-project
```

---

## 📦 Step 3: Install PHP Dependencies

```bash
composer install
```

---

## 📁 Step 4: Create and Configure .env File

```bash
copy .env.example .env
```

Edit the `.env` file and set your database credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_app
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🔑 Step 5: Generate Application Key

```bash
php artisan key:generate
```

---

## 🧪 Step 6: Run Migrations (and Seeders if needed)

```bash
php artisan migrate
# Optional: php artisan db:seed
```

---

## 🚀 Step 7: Start Laravel Development Server

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000` in your browser.

---

## 🧾 Optional: Install Frontend Dependencies

If your project includes frontend assets:

```bash
npm install
npm run dev
```

---

## 🧹 Common Issues

- If changes in `.env` are not reflecting:
  ```bash
  php artisan config:clear
  ```

- If permission issues occur (rare on Windows), try running Git Bash or CMD as Administrator.

---

## ✅ Done!

You're now ready to start building with Laravel on Windows using XAMPP 🚀
