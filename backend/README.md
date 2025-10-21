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
- `learning_paths` - Employee learning and development paths
- `courses` - Training courses and resources
- `performance_review_cycles` - Performance review periods
- `performance_reviews` - Individual performance reviews
- `promotion_candidates` - Employee promotion requests
- `promotion_workflows` - Configurable approval workflows
- `succession_roles` - Critical roles for succession planning
- `succession_candidates` - Succession plan candidates
- `pips` - Performance Improvement Plans
- `pip_checkins` - PIP progress check-ins
- `notes` - Employee notes and observations
- `retention_risk_snapshots` - Employee retention risk tracking
- `audit_logs` - Complete audit trail for talent management

### 🎓 Learning Paths (`/api/learning`)

Complete learning and development management system with course assignments and progress tracking.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/learning-paths` | ✅ | List all learning paths |
| `POST` | `/learning-paths` | ✅ | Create learning path |
| `GET` | `/learning-paths/{id}` | ✅ | Get learning path details |
| `PUT` | `/learning-paths/{id}` | ✅ | Update learning path |
| `POST` | `/learning-paths/{id}/assign` | ✅ | Assign to employees |
| `GET` | `/learning-paths/{id}/progress` | ✅ | Get completion progress |
| `GET` | `/courses` | ✅ | List available courses |
| `POST` | `/courses` | ✅ | Create new course |
| `GET` | `/courses/{id}` | ✅ | Get course details |

### ⭐ Performance Reviews (`/api/performance-reviews`)

Comprehensive performance review system with cycles, competencies, and feedback management.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/cycles` | ✅ | List review cycles |
| `POST` | `/cycles` | ✅ | Create review cycle |
| `GET` | `/cycles/{id}` | ✅ | Get cycle details |
| `GET` | `/cycles/{id}/reviews` | ✅ | Get cycle reviews |
| `POST` | `/reviews` | ✅ | Create review |
| `GET` | `/reviews/{id}` | ✅ | Get review details |
| `PUT` | `/reviews/{id}/submit` | ✅ | Submit review |
| `GET` | `/reviews/{id}/feedback` | ✅ | Get review feedback |

### 🚀 Talent Management (`/api/talent`)

Complete talent management ecosystem for career advancement, succession planning, and employee development.

#### 📈 Promotions (`/api/talent/promotions`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | List promotion requests |
| `POST` | `/` | ✅ | Create promotion request |
| `GET` | `/{id}` | ✅ | Get promotion details |
| `PUT` | `/{id}` | ✅ | Update promotion |
| `POST` | `/{id}/submit` | ✅ | Submit for approval |
| `POST` | `/{id}/withdraw` | ✅ | Withdraw request |
| `GET` | `/{id}/timeline` | ✅ | Get approval timeline |
| `GET` | `/workflows` | ✅ | List available workflows |
| `GET` | `/pending-approvals` | ✅ | Get pending approvals |
| `POST` | `/approvals/{approvalId}` | ✅ | Process approval |
| `GET` | `/stats` | ✅ | Get promotion statistics |

#### 🔄 Succession Planning (`/api/talent/succession`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | Get succession plan overview |
| `GET` | `/metrics` | ✅ | Get succession metrics |
| `GET` | `/critical-gaps` | ✅ | Get critical role gaps |
| `GET` | `/readiness-benchmark` | ✅ | Get readiness benchmark |
| `POST` | `/roles` | ✅ | Create succession role |
| `GET` | `/roles/{id}` | ✅ | Get succession role details |
| `POST` | `/candidates` | ✅ | Add succession candidate |
| `GET` | `/candidates/{id}` | ✅ | Get candidate details |
| `PUT` | `/candidates/{id}/readiness` | ✅ | Update readiness status |
| `POST` | `/candidates/{id}/learning-path` | ✅ | Assign learning path |
| `POST` | `/candidates/{id}/promote` | ✅ | Promote candidate |
| `GET` | `/employees/{employeeId}/opportunities` | ✅ | Get employee opportunities |

#### 📊 Performance Improvement Plans (`/api/talent/pips`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | List PIPs with filters |
| `POST` | `/` | ✅ | Create new PIP |
| `GET` | `/{id}` | ✅ | Get PIP details |
| `PUT` | `/{id}/status` | ✅ | Update PIP status |
| `POST` | `/{id}/checkins` | ✅ | Add check-in |
| `GET` | `/{id}/report` | ✅ | Generate PIP report |
| `GET` | `/due-for-checkin` | ✅ | Get PIPs due for check-in |
| `GET` | `/overdue` | ✅ | Get overdue PIPs |
| `GET` | `/metrics` | ✅ | Get PIP metrics |
| `GET` | `/employees/{employeeId}/history` | ✅ | Get employee PIP history |

#### 📝 Employee Notes (`/api/talent/notes`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | List employee notes |
| `POST` | `/` | ✅ | Create note |
| `GET` | `/{id}` | ✅ | Get note details |
| `DELETE` | `/{id}` | ✅ | Delete note |

**Note Visibility Levels:**
- `hr_only`: Visible only to HR team
- `manager_chain`: Visible to employee's management chain
- `employee_visible`: Visible to the employee

#### 🎯 Retention Risk Tracking (`/api/talent/retention-risk`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | List retention risk snapshots |
| `POST` | `/` | ✅ | Create risk snapshot |
| `GET` | `/employees/{employeeId}/current` | ✅ | Get current risk level |
| `GET` | `/high-risk` | ✅ | Get high-risk employees |

**Risk Levels:** `low`, `medium`, `high`

**Common Risk Factors:**
- `workload_high` - Excessive workload
- `compensation_below_market` - Below-market compensation
- `limited_growth_opportunities` - Limited career growth
- `poor_work_life_balance` - Work-life balance issues
- `team_dynamics_issues` - Team relationship problems
- `role_mismatch` - Skills-role mismatch

### 👨‍💼 Manager Performance Reviews (`/api/v1/manager`)

Comprehensive manager dashboard for team performance management, promotion recommendations, succession planning, and career development.

#### 📋 Team Review Management (`/api/v1/manager/team-reviews`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/cycles` | ✅ | Get review cycles with team completion stats |
| `GET` | `/cycles/{cycle_id}/reviewees` | ✅ | Get reviewees with performance data and badges |
| `POST` | `/cycles/{cycle_id}/reminders` | ✅ | Send targeted reminders (rate limited) |
| `GET` | `/team-summary` | ✅ | Get team performance analytics summary |
| `GET` | `/team-members` | ✅ | Get team members with performance data |

**Team Review Features:**
- **Cycle Management**: Track review cycles with completion percentages and team headcount
- **Reviewee Oversight**: View detailed performance data with AI-powered badges
- **Reminder System**: Send targeted reminders to self, peer, or all reviewers (4-hour rate limit)
- **Performance Analytics**: Team completion rates, average scores, high performer percentages
- **Badge Classification**: Automatic categorization as "Ready", "High Potential", "Developing", or "Solid"

#### 🚀 Promotion Recommendations (`/api/v1/manager/promotions`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/` | ✅ | Create promotion recommendation |
| `GET` | `/` | ✅ | Get manager's promotion recommendations |
| `PATCH` | `/{promotion_id}/withdraw` | ✅ | Withdraw pending recommendation |

**Promotion Management Features:**
- **Recommendation Creation**: Create detailed promotion proposals with justification and compensation adjustments
- **Approval Workflow**: Track promotion status through pending, approved, rejected, withdrawn states
- **Compensation Planning**: Define minimum and maximum compensation adjustment ranges
- **Role Hierarchy**: Automatic detection of promotion levels (standard, double, lateral, demotion)
- **Access Control**: Managers can only recommend their direct and indirect reports

#### 🔄 Succession Planning (`/api/v1/manager/succession`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/` | ✅ | Add employee to succession pool |

**Succession Planning Features:**
- **Pool Management**: Create and manage role-based succession pools
- **Readiness Tracking**: Track candidate readiness with timeframes (ready_now, 3-6m, 6-12m, 12-24m)
- **Development Plans**: Assign learning paths and mentors to succession candidates
- **Risk Assessment**: Calculate succession readiness scores with color-coded risk levels
- **Priority Management**: Manage succession pools by priority (high, medium, low) and business impact

#### 🛤️ Career Path Assignments (`/api/v1/manager/career-paths`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/assign` | ✅ | Assign career development path to employee |

**Career Development Features:**
- **Path Assignment**: Assign learning paths targeting specific roles
- **Progress Tracking**: Monitor completion progress with milestone management
- **Timeline Management**: Set target completion dates with automatic progress calculation
- **Learning Integration**: Seamless integration with Learning Management System
- **Milestone System**: Track key development milestones with completion status

#### 📚 Reference Data (`/api/v1/manager/reference`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/roles` | ✅ | Get available roles for promotion/succession |
| `GET` | `/learning-paths` | ✅ | Get available learning paths (optionally filtered by role) |

### Database Schema - Manager Reviews

**Core Tables:**
- `teams` - Team hierarchy and organizational structure
- `team_members` - Manager-employee relationships with reporting structure
- `promotion_recommendations` - Promotion workflow with approval tracking
- `succession_pool` - Role-based succession planning pools
- `manager_succession_candidates` - Succession candidate management with readiness tracking
- `career_paths_assigned` - Career development path assignments with progress tracking

**Key Relationships:**
- Teams have hierarchical structure with managers and members
- Promotion recommendations link employees to target roles with approval workflow
- Succession pools contain candidates with readiness assessment and development plans
- Career path assignments track learning progress toward target roles
- `lack_of_recognition` - Insufficient recognition
- `manager_relationship` - Manager relationship issues

#### 📋 Audit Logs (`/api/talent/audit-logs`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | ✅ | List audit logs |
| `GET` | `/{entityType}/{entityId}` | ✅ | Get entity-specific logs |

**Tracked Actions:** `created`, `updated`, `deleted`, `status_changed`, `approved`, `rejected`, `submitted`, `withdrawn`

## 🎯 Talent Management Features

### Core Capabilities

1. **Promotion Workflows**
   - Configurable approval processes
   - Role-based approval routing
   - Salary change management
   - Skills and achievements tracking
   - Timeline and status tracking

2. **Succession Planning**
   - Critical role identification
   - Successor readiness assessment
   - Development plan integration
   - Gap analysis and reporting
   - Bench strength analytics

3. **Performance Improvement Plans**
   - Goal setting and tracking
   - Regular check-in management
   - Mentor assignment
   - Progress rating system
   - Learning path integration

4. **Employee Development**
   - Learning path assignments
   - Skill development tracking
   - Career progression planning
   - Mentorship programs

5. **Retention Management**
   - Risk factor assessment
   - Monthly snapshots
   - Trend analysis
   - Early warning system

6. **Comprehensive Auditing**
   - Full activity tracking
   - Compliance reporting
   - Change history
   - Access logging

### Integration Points

- **Learning Paths**: PIPs and succession planning integrate with learning management
- **Performance Reviews**: Connects with promotion and succession decisions
- **User Management**: Role-based access and employee hierarchy
- **Audit Logs**: Complete traceability across all talent activities

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