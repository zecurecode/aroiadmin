# WordPress/WooCommerce and Laravel Integration Documentation

## Overview

This document describes the integration between the WordPress/WooCommerce frontend and the Laravel admin backend for the Aroi Food Truck system. The integration has been redesigned to use Laravel API endpoints instead of direct database connections.

## Architecture

### Old Architecture
- WordPress directly connected to MySQL database
- `functions.php` and `adminfunctions.php` used direct SQL queries
- No centralized business logic
- Duplicate code between WordPress and admin system

### New Architecture
- WordPress communicates with Laravel via REST API
- All database operations handled by Laravel
- Centralized business logic in Laravel
- Consistent data handling across systems

## File Structure

### WordPress Files
- **`functions-laravel.php`**: New WordPress functions file that uses Laravel API
- **`adminfunctions-laravel.php`**: New admin functions that call Laravel endpoints
- **`functions.php`** (legacy): Old file with direct database connections
- **`adminfunctions.php`** (legacy): Old file with SQL queries

### Laravel Files
- **`app/Http/Controllers/Api/WordPressController.php`**: API controller for WordPress integration
- **`app/Http/Controllers/Api/ApiController.php`**: Main API controller for order processing
- **`routes/api.php`**: API route definitions

## API Endpoints

### WordPress Integration Endpoints

All endpoints are prefixed with `/api/v1/wordpress/`

#### Location Information
- `GET /location/{siteId}` - Get location details
- `GET /location/{siteId}/delivery-time` - Get delivery time for location
- `GET /location/{siteId}/opening-hours` - Get current day's opening hours
- `GET /location/{siteId}/all-hours` - Get weekly schedule
- `GET /location/{siteId}/is-open` - Check if location is currently open
- `POST /location/{siteId}/update-status` - Update location status

#### Order Management
- `POST /orders` - Create new order
- `POST /orders/mark-paid` - Mark order as paid
- `GET /process-orders` - Process pending orders to PCKasse

## WordPress Functions

### Core Functions

#### `getCaller()`
Returns the current WordPress site ID (multisite blog ID).

#### `gettid_function($id)`
Gets delivery/preparation time for a location.
- **Old**: Direct SQL query to `leveringstid` table
- **New**: API call to `/location/{siteId}/delivery-time`

#### `gettid_function2($atts)`
Shortcode function to display opening hours.
- **Old**: Complex SQL queries with inline status updates
- **New**: API call to `/location/{siteId}/opening-hours`

#### `isItOpenNow($vogn)`
Checks if a location is currently open.
- **Old**: Direct time calculations from database
- **New**: API call to `/location/{siteId}/is-open`

#### `getOpen($id)` / `getClose($id)`
Get opening/closing times for current day.
- **Old**: SQL query to `apningstid` table
- **New**: API call to `/location/{siteId}/opening-hours`

#### `getStatus($id)`
Get location status (open/closed).
- **Old**: SQL query to `apningstid` table
- **New**: API call to `/location/{siteId}/opening-hours`

### WooCommerce Integration

#### `your_order_details($order_id)`
Creates order record when WooCommerce order is placed.
- **Old**: Direct SQL INSERT to `orders` table
- **New**: POST to `/orders` API endpoint

#### `orderIsPaid($order_id)`
Updates order status when payment is completed.
- **Old**: SQL UPDATE and cURL to legacy `api.php`
- **New**: POST to `/orders/mark-paid` and `/process-orders`

#### `action_woocommerce_before_order_notes($checkout)`
Displays pickup time selection during checkout.
- Uses opening hours from API to generate time slots
- Considers current time and preparation time
- Shows appropriate messages for closed locations

## Location Mapping

### Site IDs to Location Names
```php
7  => 'Namsos'
4  => 'Lade'
6  => 'Moan'
5  => 'Gramyra'
10 => 'Frosta'
11 => 'Hell'
12 => 'Steinkjer'
```

### Site IDs to User IDs (Legacy)
```php
7  => 10  // Namsos
4  => 11  // Lade
6  => 12  // Moan
5  => 13  // Gramyra
10 => 14  // Frosta
11 => 16  // Hell
12 => 17  // Steinkjer
```

### PCKasse License Mapping
```php
7  => 6714   // Namsos
4  => 12381  // Lade
6  => 5203   // Moan
5  => 6715   // Gramyra
10 => 14780  // Frosta
11 => N/A    // Hell
12 => 30221  // Steinkjer
```

## Database Tables Used

### `apningstid` (Opening Hours)
- Stores opening hours for all locations
- Each location has columns: `open{location}`, `close{location}`, `status{location}`, `notes{location}`
- Days stored in Norwegian: Mandag, Tirsdag, etc.

### `orders`
- Stores WooCommerce order information
- Key fields: `ordreid`, `site`, `paid`, `curl`, `ordrestatus`
- Links to WooCommerce order IDs

### `leveringstid`
- Stores preparation time for each location
- Maps user ID to preparation time in minutes

### `avdeling` / `_avdeling`
- Location information and configuration
- Alternative tables for new system structure

## Shortcodes

### `[gettid]`
Displays preparation/delivery time for current location.

### `[gettid2 site="X"]`
Displays opening hours and status for specified location.

## Migration Notes

### To migrate from old to new system:

1. **Update WordPress theme files**:
   - Replace `functions.php` with `functions-laravel.php`
   - Replace `adminfunctions.php` with `adminfunctions-laravel.php`

2. **Configure API endpoint**:
   - Update the `$baseUrl` in `callLaravelAPI()` function
   - Ensure Laravel API is accessible from WordPress

3. **Test critical functions**:
   - Order creation flow
   - Payment processing
   - Opening hours display
   - Pickup time selection

4. **Monitor for issues**:
   - Check Laravel logs for API errors
   - Verify orders are being created correctly
   - Ensure SMS notifications still work

### Rollback procedure:
If issues occur, you can quickly rollback by:
1. Renaming original files back
2. Ensuring database credentials are still valid
3. Testing order flow

## Security Considerations

1. **API Authentication**: Currently no authentication on API endpoints. Consider adding API keys or OAuth.
2. **HTTPS**: Ensure all API calls use HTTPS
3. **Input Validation**: Laravel validates all inputs, WordPress should too
4. **Error Handling**: API calls include error handling but should log failures

## Performance Optimization

1. **Caching**: Consider caching opening hours API responses
2. **Batch Operations**: Process multiple orders in single API call
3. **Connection Pooling**: Reuse cURL connections where possible

## Future Improvements

1. Add webhook support for real-time order updates
2. Implement proper API authentication
3. Add rate limiting to prevent abuse
4. Create admin UI for managing API connections
5. Add health check endpoints
6. Implement proper error logging and monitoring