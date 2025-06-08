# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Aroi Food Truck admin system - a multi-location restaurant management platform. The codebase contains:
- **Laravel Admin** (`/laravel-admin/`) - Modern Laravel 12 replacement system
- **Legacy PHP Admin** (`/admin/admin/`) - Original system being phased out
- **WordPress Integration** - Frontend runs on WordPress/WooCommerce

The system manages food orders from multiple truck locations, integrates with PCKasse POS, and sends SMS notifications to customers.

## Common Development Commands

### Laravel Setup & Development
```bash
cd laravel-admin

# Initial setup
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
php artisan storage:link

# Development (runs all services concurrently)
composer run dev
# Or run individually:
php artisan serve
npm run dev

# Testing
php artisan test
php artisan test --filter=AuthenticationTest

# Database operations
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker

# Create admin user
php artisan make:admin username password siteid license
```

### Build & Deployment
```bash
# Production build
npm run build
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## High-Level Architecture

### Database Structure
The system uses an existing MySQL database (`admin_aroi`) with these core tables:
- `users` - Admin users linked to specific locations
- `orders` - Food orders from WooCommerce
- `apningstid` - Opening hours for each location
- `avdeling` - Location/department information

### API Integration Flow
1. WordPress/WooCommerce creates order via `POST /api/v1/orders`
2. Order stored as unpaid in database
3. Payment completion triggers `POST /api/v1/orders/mark-paid`
4. Cron job (`/api/v1/process-orders`) sends paid orders to PCKasse POS
5. SMS notifications sent via Teletopia API

### Authentication System
- Laravel Breeze with custom modifications
- Username-based login (not email)
- Master password support for legacy compatibility
- Location-based access control (users see only their site's orders)

### Key Integration Points
- **PCKasse POS**: Orders sent via cURL to `https://db.pckasse.no/api/orders/store`
- **Teletopia SMS**: Customer notifications via `https://prddb2.teletopia.com/ttsengine/`
- **WordPress**: Orders received from WooCommerce via custom API

### Location Configuration
Each location has unique IDs and PCKasse licenses:
- Namsos (ID: 7, License: 6714)
- Lade (ID: 4, License: 12381)
- Moan (ID: 6, License: 5203)
- Gramyra (ID: 5, License: 6715)
- Frosta (ID: 10, License: 14780)
- Hell (ID: 11, License: N/A)
- Steinkjer (ID: 12, License: 30221)

## Important Considerations

1. **Database Connectivity**: The system connects to a remote MySQL server. Ensure database credentials are properly configured in `.env`.

2. **Legacy Compatibility**: When modifying order processing or API endpoints, ensure compatibility with the WordPress frontend that still references legacy endpoints.

3. **Session Management**: The system uses database sessions. Clear old sessions periodically with `php artisan session:clear`.

4. **Error Handling**: Orders that fail to send to PCKasse are logged. Check `storage/logs/laravel.log` for debugging.

5. **Testing**: Always test order flow end-to-end when modifying:
   - Order creation via API
   - Payment marking
   - POS integration
   - SMS notifications