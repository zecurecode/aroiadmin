# MultiSide Aroi Integration

**Version:** 2.0.0
**Author:** InfoDesk AS
**License:** GPL v2 or later
**Requires at least:** WordPress 6.0
**Requires PHP:** 7.4
**WooCommerce:** 7.0+

Modern WordPress plugin for Aroi Food Truck multi-location management. Fully replaces the legacy manual system with automated order processing, PCKasse POS integration, and SMS notifications.

---

## Features

✅ **Automated Order Processing**
- Automatically creates orders in admin database when WooCommerce orders are placed
- Marks orders as paid when payment is completed
- **CRITICAL:** Immediately sends paid orders to PCKasse POS system
- **CRITICAL:** Automatically sends SMS confirmation to customers

✅ **Opening Hours Management**
- Uses modern `_apningstid` database table
- Real-time open/closed status checking
- Automatic pickup time calculation based on delivery times
- Multi-location support (Namsos, Lade, Moan, Gramyra, Frosta, Hell, Steinkjer)

✅ **Department Cards Shortcode**
- Beautiful card-based display of all locations
- Shows real-time opening hours and status
- Responsive grid layout
- Customizable columns and options

✅ **Smart Checkout Experience**
- Visual open/closed status notice
- Dynamic pickup time selector with 15-minute intervals
- Prevents orders during closed hours with helpful messaging
- Shows estimated preparation time

✅ **PCKasse POS Integration**
- Automatic order transmission to PCKasse via API
- License-based routing to correct location
- Error logging and retry logic
- HTTP status code tracking

✅ **SMS Notifications (Teletopia)**
- Instant order confirmation SMS to customers
- Order ready notifications
- Admin alerts for unpaid orders
- Norwegian phone number normalization (+47 format)

---

## Installation

### 1. Upload Plugin

```bash
# Via FTP/SFTP
Upload the multiside-aroi-integration folder to /wp-content/plugins/

# Via WordPress Admin
Plugins → Add New → Upload Plugin → Choose ZIP file
```

### 2. Activate Plugin

```
WordPress Admin → Plugins → Activate "MultiSide Aroi Integration"
```

### 3. Configure Site ID

Add to your `wp-config.php`:

```php
// Set your site/location ID (required for single-site installations)
define('AROI_SITE_ID', 7); // Change to your site ID

// Site IDs:
// 4  = Lade
// 5  = Gramyra
// 6  = Moan
// 7  = Namsos
// 10 = Frosta
// 11 = Hell
// 12 = Steinkjer
```

**Note:** For WordPress Multisite, the plugin automatically uses `get_current_blog_id()`.

### 4. Verify Database Connection

The plugin connects to the existing `admin_aroi` database. Ensure these credentials are correct in the main plugin file:

```php
define('MULTISIDE_AROI_DB_HOST', 'localhost:3306');
define('MULTISIDE_AROI_DB_NAME', 'admin_aroi');
define('MULTISIDE_AROI_DB_USER', 'adminaroi');
define('MULTISIDE_AROI_DB_PASS', 'b^754Xws');
```

---

## Usage

### Shortcodes

#### 1. Department Cards

Display all locations as beautiful cards:

```
[aroi_department_cards]
```

**Options:**

```
[aroi_department_cards sites="4,5,6,7" columns="3" show_hours="yes" show_status="yes"]
```

- `sites` - Comma-separated site IDs or "all" (default: all)
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `show_hours` - Show opening hours: yes/no (default: yes)
- `show_status` - Show open/closed badge: yes/no (default: yes)

#### 2. Opening Hours

Display opening hours for current location:

```
[aroi_opening_hours]
```

With specific site:

```
[aroi_opening_hours site="7"]
```

#### 3. Delivery Time

Show delivery time in minutes:

```
[aroi_delivery_time]
```

With specific site:

```
[aroi_delivery_time site="7"]
```

---

## How It Works

### Order Flow

```
1. CUSTOMER PLACES ORDER
   ↓
   WooCommerce creates order
   ↓
   woocommerce_new_order hook triggered
   ↓
   Plugin creates order in admin_aroi.orders table
   - paid = 0 (unpaid)
   - curl = 0 (not sent to PCKasse)
   - sms = 0 (SMS not sent)

2. CUSTOMER COMPLETES PAYMENT
   ↓
   woocommerce_payment_complete hook triggered
   ↓
   Plugin marks order as paid = 1
   ↓
   IMMEDIATELY sends order to PCKasse POS
   ↓
   IMMEDIATELY sends SMS to customer
   ↓
   Updates database:
   - paid = 1
   - curl = 200/201 (HTTP response code)
   - sms = 1
   - curltime = NOW()
```

### Critical Requirements

⚠️ **IMPORTANT:** This plugin MUST:

1. **Send to PCKasse on EVERY paid order** - No exceptions
2. **Send SMS on EVERY paid order** - Customer confirmation required
3. **Use ONLY _apningstid table** for opening hours - Legacy table not used

---

## Database Tables Used

### orders
Main order storage table with these critical fields:

| Field | Type | Description |
|-------|------|-------------|
| `ordreid` | INT | WooCommerce order ID |
| `site` | INT | Location ID (4-12) |
| `paid` | BOOLEAN | 0=unpaid, 1=paid |
| `curl` | INT | PCKasse status (0=not sent, 200/201=sent) |
| `sms` | BOOLEAN | 0=not sent, 1=sent |
| `telefon` | VARCHAR | Customer phone (+47 format) |
| `fornavn` | VARCHAR | First name |
| `etternavn` | VARCHAR | Last name |
| `hentes` | VARCHAR | Pickup time |

### _apningstid
Opening hours table (one row per location):

| Field | Type | Description |
|-------|------|-------------|
| `AvdID` | INT | Location ID |
| `Navn` | VARCHAR | Location name |
| `ManStart`, `ManStopp` | TIME | Monday hours |
| `TirStart`, `TirStopp` | TIME | Tuesday hours |
| `ManStengt` | BOOLEAN | Monday closed flag |
| `url` | VARCHAR | Website URL |

### leveringstid
Delivery time configuration:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | User/Site ID |
| `tid` | INT | Delivery time in minutes |

---

## API Integration Details

### PCKasse POS Integration

**Endpoint:** `https://min.pckasse.no/QueueGetOrders.aspx?licenceno={license}`

**License Mapping:**
```php
7  => 6714   // Namsos
4  => 12381  // Lade
6  => 5203   // Moan
5  => 6715   // Gramyra
10 => 14780  // Frosta
12 => 30221  // Steinkjer
15 => 14946  // Malvik
```

**Success Codes:** 200, 201

### SMS Integration (Teletopia)

**Endpoint:** `https://api1.teletopiasms.no/gateway/v3/plain`

**Message Template:**
```
Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan.
Vi sender deg en ny SMS når maten er klar til henting.
Ditt referansenummer er {ORDER_ID}
```

**Credentials:** Configured in `class-sms-service.php`

**Phone Format:** Automatically normalized to +47XXXXXXXX

---

## Checkout Page Integration

The plugin automatically adds these elements to the WooCommerce checkout page:

### 1. Opening Status Notice

**When OPEN:**
```
Åpen for henting i dag! Åpent til 23:00.
```
(Green background)

**When CLOSED:**
```
Vognen er stengt. Du kan fortsatt bestille for neste dag.
Vi åpner klokken 10:30.
```
(Red background)

### 2. Pickup Time Selector

- Required field with dropdown
- Shows available times in 15-minute intervals
- Automatically calculates earliest pickup time:
  - Current time + delivery time (e.g., +30 minutes)
  - Rounded to next 15-minute interval
- Shows tomorrow's times if location is closed
- Displays preparation time: "Det tar ca. 30 minutter før bestillingen er klar"

---

## File Structure

```
multiside-aroi-integration/
├── multiside-aroi-integration.php   # Main plugin file
├── README.md                         # This file
├── includes/
│   ├── class-database.php           # Database connection handler
│   ├── class-order-handler.php      # Core order processing (NEW ORDER + PAYMENT)
│   ├── class-sms-service.php        # SMS/Teletopia integration
│   ├── class-pckasse-service.php    # PCKasse POS integration
│   ├── class-opening-hours.php      # Opening hours from _apningstid
│   ├── class-department-cards.php   # Department cards shortcode
│   └── class-checkout-manager.php   # Checkout page enhancements
└── assets/
    ├── css/
    │   ├── department-cards.css     # Card styling
    │   ├── checkout.css             # Checkout page styling
    │   └── admin.css                # Admin area styling
    └── js/
        └── frontend.js              # Frontend JavaScript
```

---

## Key Classes

### Multiside_Aroi_Order_Handler
**Handles:** Order creation and payment processing

**Critical Methods:**
- `on_order_created()` - Creates order in database (unpaid)
- `on_payment_complete()` - **CRITICAL** - Sends to PCKasse + SMS

### Multiside_Aroi_PCKasse_Service
**Handles:** PCKasse POS integration

**Methods:**
- `send_order($site_id)` - Sends order to PCKasse via API
- `get_license($site_id)` - Gets PCKasse license for location

### Multiside_Aroi_SMS_Service
**Handles:** SMS notifications via Teletopia

**Methods:**
- `send_order_confirmation($order_id, $phone)` - Initial SMS
- `send_order_ready($order_id, $phone, $name, $location)` - Ready notification
- `send_admin_alert($order_id, $site_id, $minutes)` - Unpaid order alert

### Multiside_Aroi_Opening_Hours
**Handles:** Opening hours from _apningstid table

**Methods:**
- `get_hours($site_id, $day)` - Get hours for specific day
- `is_open_now($site_id)` - Check if location is currently open
- `get_delivery_time($site_id)` - Get preparation time

### Multiside_Aroi_Checkout_Manager
**Handles:** Checkout page enhancements

**Methods:**
- `display_opening_status_notice()` - Shows open/closed notice
- `display_pickup_time_selector()` - Pickup time dropdown

---

## Logging & Debugging

All critical operations are logged to WordPress error log:

```php
// View logs (if WP_DEBUG is enabled)
tail -f wp-content/debug.log

// Or check server error logs
/var/log/apache2/error.log
```

**Log Examples:**
```
MultiSide Aroi: Order created - WC Order: 12345 - DB ID: 789 - Site: 7
MultiSide Aroi: Payment complete triggered for order 12345
MultiSide Aroi: Order 12345 marked as PAID - Now sending to PCKasse and SMS
MultiSide Aroi: PCKasse send SUCCESS - Order: 12345 - HTTP: 200
MultiSide Aroi: SMS sent SUCCESS - Order: 12345 - Phone: +4712345678
```

---

## Troubleshooting

### Orders not sent to PCKasse

**Check:**
1. Is order marked as `paid = 1` in database?
2. Check error log for HTTP response codes
3. Verify PCKasse license is configured correctly
4. Test connection: `curl "https://min.pckasse.no/QueueGetOrders.aspx?licenceno=6714"`

### SMS not sent to customers

**Check:**
1. Is phone number in correct format (+47XXXXXXXX)?
2. Verify SMS credentials in `class-sms-service.php`
3. Check Teletopia API status
4. Review error logs for HTTP response codes

### Opening hours not displaying

**Check:**
1. Verify `_apningstid` table exists and has data
2. Check Site ID mapping in `class-opening-hours.php`
3. Ensure `AROI_SITE_ID` is defined in `wp-config.php`

### Pickup time selector not showing

**Check:**
1. Is WooCommerce active?
2. Are you on the checkout page?
3. Check browser console for JavaScript errors
4. Verify opening hours are configured

---

## Differences from Legacy System

| Feature | Legacy System | New MultiSide Plugin |
|---------|--------------|---------------------|
| Order creation | Manual hook | Automated hook |
| Opening hours | `apningstid` table | `_apningstid` table |
| PCKasse trigger | Cron job + webhook | Immediate on payment |
| SMS sending | Manual cURL | Service class |
| Code organization | Single file | Modular classes |
| Error handling | Echo to screen | Structured logging |
| Security | SQL injection risk | Prepared statements |
| Phone normalization | Manual | Automatic |

---

## Support & Development

**Developer:** InfoDesk AS
**Email:** support@infodesk.no
**Documentation:** See CLAUDE.md in project root

### Contributing

When modifying this plugin:

1. Always test order flow end-to-end
2. Verify PCKasse integration still works
3. Test SMS sending with real phone numbers
4. Check opening hours logic for all locations
5. Test both open and closed scenarios

---

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 InfoDesk AS

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## Changelog

### Version 2.0.0 (2025-01-25)
- Initial release of modern MultiSide plugin
- Fully replaces legacy WordPress integration
- Uses `_apningstid` table for opening hours
- Automated PCKasse integration on payment
- Automated SMS notifications
- Department cards shortcode
- Smart checkout experience
- Modular, object-oriented architecture
- Comprehensive error logging

---

## Quick Start Checklist

- [ ] Plugin uploaded and activated
- [ ] `AROI_SITE_ID` defined in `wp-config.php`
- [ ] Database connection verified
- [ ] Test order placed and paid
- [ ] PCKasse received order (check admin dashboard)
- [ ] Customer received SMS
- [ ] Department cards shortcode added to homepage
- [ ] Checkout page displays opening hours and pickup time
- [ ] Error logging enabled and monitored

---

**Made with ❤️ by InfoDesk AS for Aroi Food Truck**
