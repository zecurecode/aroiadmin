# Aroi Laravel Integration Plugin

A WordPress plugin that integrates WooCommerce with the Laravel-based Aroi Food Truck admin system.

## Description

This plugin replaces the legacy direct database connections with a modern API-based approach, allowing WordPress/WooCommerce to communicate with the Laravel backend for:

- Order management and processing
- Opening hours display
- Location status updates
- Pickup time selection
- SMS notifications (via Laravel)

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher
- Laravel backend with API endpoints configured

## Installation

1. Upload the `aroi-laravel-integration` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Aroi Integration' in the admin menu
4. Configure the API settings with your Laravel backend URL

## Configuration

### API Settings

1. Navigate to **Aroi Integration > Settings**
2. Enter your Laravel API base URL (e.g., `https://aroiasia.no/laravel-admin/api/v1`)
3. Set the API timeout (default: 30 seconds)
4. Configure cache duration (default: 300 seconds)

### Site Mapping

The plugin automatically maps WordPress multisite blog IDs to location IDs:

- Site ID 7 → Namsos
- Site ID 4 → Lade
- Site ID 6 → Moan
- Site ID 5 → Gramyra
- Site ID 10 → Frosta
- Site ID 11 → Hell
- Site ID 12 → Steinkjer

## Features

### Shortcodes

#### Display All Locations
```
[aroi_locations]
```
Displays all active locations as clickable cards with real-time status.

Options:
- `columns` - Number of columns (2, 3, or 4). Default: 3
- `show_map` - Show Google Maps (yes/no). Default: yes
- `show_hours` - Show opening hours (yes/no). Default: yes
- `show_phone` - Show phone number (yes/no). Default: yes
- `show_status` - Show open/closed status (yes/no). Default: yes

Example:
```
[aroi_locations columns="2" show_map="no"]
```

#### Display Single Location
```
[aroi_single_location site="7"]
```
Displays detailed information for a single location.

Options:
- `site` - Site ID (required)
- `show_map` - Show Google Maps (yes/no). Default: yes
- `show_hours` - Show opening hours (yes/no). Default: yes
- `show_phone` - Show phone number (yes/no). Default: yes
- `show_status` - Show open/closed status (yes/no). Default: yes

#### Display Delivery Time
```
[gettid]
```
Shows the preparation/delivery time for the current location.

#### Display Opening Hours
```
[gettid2 site="7"]
```
Shows opening hours and current status for a specific location.

#### Location Status
```
[aroi_location_status site="7" format="simple"]
```
Formats: `simple`, `detailed`, `widget`

#### Weekly Hours
```
[aroi_weekly_hours site="7" highlight_today="yes"]
```
Displays the weekly schedule for a location.

### WooCommerce Integration

The plugin automatically:
- Creates orders in Laravel when WooCommerce orders are placed
- Updates order status when payment is completed
- Triggers SMS notifications via Laravel
- Adds pickup time selection to checkout
- Displays pickup time in order emails and admin

### Admin Features

- **Status Page**: View API connection status and recent orders
- **Tools Page**: 
  - Migrate DSK product addons
  - Test API endpoints
  - Clear cache

## API Endpoints

The plugin communicates with these Laravel endpoints:

- `GET /wordpress/location/{siteId}` - Location details
- `GET /wordpress/location/{siteId}/delivery-time` - Delivery time
- `GET /wordpress/location/{siteId}/opening-hours` - Current hours
- `GET /wordpress/location/{siteId}/all-hours` - Weekly schedule
- `GET /wordpress/location/{siteId}/is-open` - Open status
- `POST /wordpress/location/{siteId}/update-status` - Update status
- `POST /orders` - Create order
- `POST /orders/mark-paid` - Mark order paid
- `POST /process-orders` - Process pending orders

## Hooks and Filters

### Actions

- `aroi_order_created` - Fired when order is created in Laravel
- `aroi_order_paid` - Fired when order is marked as paid
- `aroi_status_updated` - Fired when location status changes

### Filters

- `aroi_api_timeout` - Modify API timeout
- `aroi_cache_duration` - Modify cache duration
- `aroi_pickup_time_options` - Modify pickup time slots

## Troubleshooting

### API Connection Issues

1. Check the API URL in settings
2. Verify Laravel backend is accessible
3. Check WordPress debug log for errors
4. Use the Tools page to test endpoints

### Order Processing Issues

1. Ensure WooCommerce order statuses are configured correctly
2. Check that the site ID mapping is correct
3. Verify Laravel is receiving the orders
4. Check order notes for error messages

### Cache Issues

1. Clear cache from Status page
2. Set cache duration to 0 to disable
3. Check database table `wp_aroi_cache`

## Migration from Legacy System

To migrate from the old direct database system:

1. Install and activate this plugin
2. Configure API settings
3. Test with a few orders
4. Update theme to remove old functions.php code
5. Monitor for any issues

### Removing Legacy Code

In your theme's `functions.php`, remove:
- Direct database connections
- Order processing functions
- Opening hours functions

Replace with:
- This plugin handles all functionality

## Development

### Adding Custom Endpoints

```php
add_filter('aroi_api_endpoints', function($endpoints) {
    $endpoints['custom'] = '/custom/endpoint';
    return $endpoints;
});
```

### Extending API Client

```php
$api = Aroi_Laravel_Integration::get_instance()->get_api_client();
$response = $api->get('/custom/endpoint');
```

### Custom Status Display

```php
add_filter('aroi_status_html', function($html, $status_data) {
    // Modify status display
    return $html;
}, 10, 2);
```

## Support

For support, please contact the Aroi development team or create an issue in the project repository.

## Changelog

### 1.0.0
- Initial release
- API-based integration with Laravel
- WooCommerce order management
- Opening hours display
- Pickup time selection
- Admin interface
- Caching system

## License

GPL v2 or later