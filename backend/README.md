# 🚀 Projitt HR Management - Backend API

## 📖 Overview

This is the **Laravel 12** backend API for the Projitt HR Management System - a comprehensive AI-powered talent acquisition and human resources platform. The API provides robust endpoints for user management, job posting, applicant tracking, assessments, interviews, and media management.

## 🛠 Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Authentication**: Laravel Sanctum (Token-based)
- **Database**: SQLite (default) / MySQL supported
- **Build Tool**: Vite 6.2 with TailwindCSS 4.0
- **Testing**: PHPUnit 11.5
- **Code Quality**: Laravel Pint (Formatter)

## 🏃‍♂️ Quick Start

### Prerequisites

- **PHP** >= 8.2
- **Composer** >= 2.7
- **Node.js** >= 18 (for Vite build tools)
- **SQLite** (default) or **MySQL** database

### 1. Environment Setup

```bash
# Clone the repository (if not already done)
cd backend

# Install PHP dependencies
composer install

# Install Node.js dependencies for Vite
npm install

# Set up environment configuration
cp .env.example .env  # Create this file if it doesn't exist

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed database with initial data (optional)
php artisan migrate:refresh --seed

```

### 2. Development Server

```bash
# Start Laravel development server
php artisan serve
# API will be available at: http://localhost:8000

# In a separate terminal, start Vite dev server (for asset compilation)
npm run dev

# Or run both simultaneously with:
composer run dev
```

### 3. Production Build

```bash
# Build assets for production
npm run build

# Optimize Laravel for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🗂 Project Structure

```
backend/
├── app/
│   ├── Extensions/          # Custom middleware and extensions
│   ├── Helpers/            # Helper utilities (ResponseHelper.php)
│   ├── Http/
│   │   └── Controllers/    # API Controllers organized by domain
│   │       ├── Assessment/ # Assessment management
│   │       ├── Country/    # Country and location data
│   │       ├── Interview/  # Interview scheduling
│   │       ├── Job/        # Job posting and applicant management
│   │       ├── Master/     # Master data management
│   │       ├── Media/      # File upload and media handling
│   │       ├── Question/   # Question bank and coding challenges
│   │       └── User/       # User authentication and management
│   ├── Mail/              # Email templates and handlers
│   ├── Models/            # Eloquent models organized by domain
│   └── Providers/         # Service providers
├── config/                # Laravel configuration files
├── database/
│   ├── migrations/        # Database schema migrations
│   └── seeders/          # Database seeders
├── routes/               # API route definitions
│   ├── routes.php       # Main route grouping
│   ├── user.php         # User management routes
│   ├── job.php          # Job and applicant routes
│   ├── assessment.php   # Assessment routes
│   ├── interview.php    # Interview routes
│   ├── question.php     # Question management routes
│   ├── master.php       # Master data routes
│   ├── media.php        # Media upload routes
│   └── country.php      # Country/location routes
├── storage/              # File storage and logs
└── tests/               # Test suites
```

## 🔐 Authentication & Middleware

The API uses **Laravel Sanctum** for token-based authentication with custom middleware:

### Available Middleware
- `auth:sanctum` - Requires valid API token
- `applicant.onboarded` - For applicant-specific routes (onboarded users)
- `either.auth.or.onboarded` - Allows either authenticated users OR onboarded applicants

### Authentication Flow
1. **Register/Login** → Get access token
2. **Include token** in API requests: `Authorization: Bearer {token}`
3. **Refresh token** when needed
4. **Logout** to revoke tokens

## 📡 API Endpoints

### Base URL: `http://localhost:8000/api`

### 👤 User Management (`/api/user`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/register` | ❌ | Register new user |
| `PUT` | `/login` | ❌ | User login |
| `GET` | `/logout` | ✅ | User logout |
| `POST` | `/refresh-token` | ✅ | Refresh access token |
| `POST` | `/forgot-password` | ❌ | Send password reset email |
| `GET` | `/password-reset/{token}` | ❌ | Validate reset token |
| `POST` | `/password-reset` | ❌ | Reset password |
| `GET` | `/role/list-with-filters` | ❌ | List user roles |
| `POST` | `/send-applicant-otp` | ❌ | Send OTP to applicant |
| `POST` | `/verify-applicant-otp` | ❌ | Verify applicant OTP |

### 💼 Job Management (`/api/job`)

#### Recruiter/Admin Routes (Requires `auth:sanctum`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/add` | Create new job posting |
| `POST` | `/edit` | Edit job details |
| `POST` | `/edit-description` | Update job description |
| `POST` | `/edit-media` | Update job media/attachments |
| `POST` | `/edit-questions` | Update job application questions |
| `GET` | `/single/{id}` | Get single job details |
| `GET` | `/list-with-filters` | List jobs with filtering |
| `GET` | `/intellisense-search` | Smart job search |
| `POST` | `/publish` | Publish job posting |
| `POST` | `/change-status` | Change job status |

#### Applicant Routes (Requires `applicant.onboarded`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/applicant-single` | Get applicant profile |
| `POST` | `/edit-applicant-contact-info` | Update contact info |
| `POST` | `/edit-applicant-cv-cover` | Update CV and cover letter |
| `POST` | `/edit-applicant-info` | Update applicant information |
| `POST` | `/applicant-submit` | Submit job application |
| `GET` | `/get-applicant-jobs` | Get applicant's job applications |
| `POST` | `/applicant-change-email` | Change applicant email |

#### Experience Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/applicant-experience-add` | Add work experience |
| `POST` | `/applicant-experience-edit` | Edit work experience |
| `GET` | `/applicant-experience-single/{id}` | Get single experience |
| `GET` | `/applicant-experience` | List applicant's experiences |
| `DELETE` | `/applicant-experience-delete` | Delete experience |

#### Education Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/applicant-education-add` | Add education record |
| `POST` | `/applicant-education-edit` | Edit education record |
| `GET` | `/applicant-education-single/{id}` | Get single education |
| `GET` | `/applicant-education` | List applicant's education |
| `DELETE` | `/applicant-education-delete` | Delete education |

#### Certificate Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/applicant-certificate-add` | Add certificate |
| `POST` | `/applicant-certificate-edit` | Edit certificate |
| `GET` | `/applicant-certificate-single/{id}` | Get single certificate |
| `GET` | `/applicant-certificate` | List applicant's certificates |
| `DELETE` | `/applicant-certificate-delete` | Delete certificate |

#### Application Questions
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/applicant-questions-answers` | Get job application questions |
| `POST` | `/applicant-questions-answers/update` | Submit answers to questions |

### 📝 Assessment Management (`/api/assessment`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/add` | ✅ | Create new assessment |
| `POST` | `/edit` | ✅ | Edit assessment |
| `DELETE` | `/delete` | ✅ | Delete assessment |
| `GET` | `/single/{id}` | ✅ | Get single assessment |
| `GET` | `/list-with-filters` | ✅ | List assessments with filters |

### 🎯 Interview Management (`/api/interview`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/add` | ✅ | Schedule new interview |
| `GET` | `/list-with-filters` | ✅ | List interviews with filters |

### ❓ Question Bank (`/api/question`)

#### Regular Questions
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/add` | ✅ | Add new question |
| `POST` | `/edit` | ✅ | Edit question |
| `DELETE` | `/delete` | ✅ | Delete question |
| `GET` | `/single/{id}` | ✅ | Get single question |
| `GET` | `/list-with-filters` | ✅ | List questions with filters |
| `GET` | `/intellisense-search` | ✅ | Smart question search |

#### Coding Questions
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/coding/add` | ✅ | Add coding question |
| `POST` | `/coding/edit` | ✅ | Edit coding question |
| `DELETE` | `/coding/delete` | ✅ | Delete coding question |
| `GET` | `/coding/single/{id}` | ✅ | Get single coding question |
| `GET` | `/coding/list-with-filters` | ✅ | List coding questions |

### 🗂 Master Data (`/api/master`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/add` | 🔄 | Add master data entry |
| `POST` | `/edit` | 🔄 | Edit master data |
| `DELETE` | `/delete` | 🔄 | Delete master data |
| `GET` | `/single/{id}` | 🔄 | Get single master data |
| `GET` | `/list-with-filters` | 🔄 | List master data with filters |
| `GET` | `/intellisense-search` | 🔄 | Smart master data search |

*🔄 = Requires `either.auth.or.onboarded` middleware*

### 📁 Media Management (`/api/media`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/add` | 🔄 | Upload file/media |
| `GET` | `/single/{id}` | 🔄 | Get media details |
| `DELETE` | `/delete` | 🔄 | Delete media file |

### 🌍 Country Data (`/api/country`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | 🔄 | List all countries |

## 📊 Database Schema

### Key Tables
- `users` - User accounts and authentication
- `roles` - User roles and permissions
- `jobs` - Job postings
- `job_applicants` - Job applications
- `job_applicant_experiences` - Work experience records
- `job_applicant_educations` - Education records
- `job_applicant_certificates` - Certification records
- `job_applicant_question_answers` - Application question responses
- `assessments` - Assessment templates
- `interviews` - Interview scheduling
- `questions` - Question bank
- `coding_questions` - Coding challenge questions
- `master` - Master data/lookup tables
- `media` - File uploads and media
- `countries` - Country/location data

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Feature/UserTest.php
```

## 🔧 Development Tools

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run Laravel Pint check
./vendor/bin/pint --test

# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Management
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Reset database
php artisan migrate:reset

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create new model
php artisan make:model ModelName -m
```

## 🚀 Deployment

### Environment Configuration
Create `.env` file with production settings:

```env
APP_NAME="Projitt HR Management"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projitt_hr
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Email configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

# Add other production configurations as needed
```

### Production Deployment Steps
```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🐛 Troubleshooting

### Common Issues

1. **Permission Denied (storage/logs)**
   ```bash
   chmod -R 775 storage/
   chown -R www-data:www-data storage/
   ```

2. **Database Connection Issues**
   - Check `.env` database configuration
   - Ensure database exists and credentials are correct
   - For SQLite: ensure `database/database.sqlite` exists

3. **Token Issues**
   - Clear cache: `php artisan cache:clear`
   - Regenerate app key: `php artisan key:generate`

4. **Migration Errors**
   ```bash
   php artisan migrate:reset
   php artisan migrate
   ```

## 📝 API Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  },
  "meta": {
    "pagination": {
      "current_page": 1,
      "total": 100,
      "per_page": 15
    }
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## 🤝 Contributing

1. Follow the project's branch naming convention: `hrmprojitt_feature/your-feature`
2. Write tests for new features
3. Run code quality checks before submitting
4. Follow Laravel coding standards
5. Update this documentation when adding new endpoints

## 📞 Support

For backend-related issues:
- Check Laravel logs: `storage/logs/laravel.log`
- Review API documentation above
- Ensure proper authentication headers
- Verify middleware requirements for protected routes

---

**Built with ❤️ using Laravel 12 & PHP 8.2**