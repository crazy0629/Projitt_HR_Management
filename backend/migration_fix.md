# Migration Fix Guide

## Issue Description
You're encountering a "Table 'promotion_candidates' already exists" error when running migrations.

## Root Cause
This happens when:
1. Partial migrations were run previously
2. Migration tracking is out of sync with actual database state
3. Database state differs between environments

## Solution Steps

### Option 1: Fresh Migration (Recommended for Development)
```bash
# Navigate to backend directory
cd backend

# Drop all tables and re-run all migrations from scratch
php artisan migrate:fresh

# Optional: Seed the database with sample data
php artisan db:seed
```

### Option 2: Reset Migration Tracking
```bash
# Navigate to backend directory
cd backend

# Rollback all migrations
php artisan migrate:reset

# Re-run all migrations
php artisan migrate

# Optional: Seed the database
php artisan db:seed
```

### Option 3: Manual Database Reset (If above fails)
```bash
# Navigate to backend directory
cd backend

# Drop the database file (SQLite)
rm database/database.sqlite

# Create new empty database file
touch database/database.sqlite

# Run all migrations
php artisan migrate

# Optional: Seed the database
php artisan db:seed
```

### Option 4: Check Specific Table (Diagnostic)
```bash
# Check if the promotion_candidates table actually exists
php artisan tinker
>>> Schema::hasTable('promotion_candidates');
>>> exit
```

## Migration Order Fixed
The following migration dependencies have been resolved:

1. **Categories System**: 
   - categories (16:03:15) → courses (16:03:29) ✅

2. **Promotion System**: 
   - promotion_workflows (17:53:01) → promotion_candidates (17:53:02) ✅

## Verification Steps
After running the fix:

1. Check migration status:
```bash
php artisan migrate:status
```

2. Verify all tables exist:
```bash
php artisan tinker
>>> Schema::getTableListing();
>>> exit
```

3. Clear caches:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

4. Run tests to verify everything works:
```bash
php artisan test
```

## Notes
- Always backup your database before running migration fixes
- `migrate:fresh` will delete ALL data in your database
- The migration order has been fixed in the codebase
- All foreign key constraints are now properly resolved