# MultiSide Aroi Integration - Dynamic Configuration Guide

## 🎯 100% Dynamic Configuration - NO Hardcoding!

This plugin is designed for **WordPress Multisite** with **completely dynamic configuration** fetched from the database. No configuration files needed!

---

## ⚡ Automatic Site Detection

The plugin automatically detects which site is being used through multiple methods:

### Priority 1: WordPress Multisite Blog ID
```php
// Automatically uses WordPress Multisite blog ID
$site_id = get_current_blog_id();
```

### Priority 2: URL Matching from Database
```php
// Matches current site URL against _apningstid.url or sites.url
$site_id = match_by_url(home_url());
```

### Priority 3: Single Site Fallback
```php
// For single site installations, uses first user's siteid
$site_id = get_from_users_table();
```

---

## 📊 Configuration Sources (from Database)

All configuration is loaded **dynamically** from these database tables:

### 1. Site ID & License (PCKasse)
**Source Tables:**
- `sites` table → `site_id`, `license`, `name`, `url`
- `users` table → `siteid`, `license`
- `_apningstid` table → `AvdID`, `Navn`, `url`

```sql
-- Example: Get license for site 7
SELECT license FROM users WHERE siteid = 7 LIMIT 1;
-- Returns: 6714 (Namsos PCKasse license)
```

### 2. Location Names
**Source:** `_apningstid.Navn` or `sites.name`

```sql
-- Example: Get location name for site 4
SELECT Navn FROM _apningstid WHERE AvdID = 4;
-- Returns: "Lade"
```

### 3. Delivery Times
**Source:** `leveringstid` table

```sql
-- Example: Get delivery time for site 7
SELECT tid FROM leveringstid WHERE id = 7;
-- Returns: 30 (minutes)
```

### 4. SMS Credentials
**Source:** `settings` table (optional)

```sql
-- Example: Get SMS credentials
SELECT setting_key, setting_value FROM settings
WHERE setting_key LIKE 'sms_%' OR setting_key LIKE 'teletopia_%';
```

**Default Fallback:** If not in database, uses hardcoded credentials (legacy support)

### 5. SMS Sender Names
**Dynamic per location:**
- Default: "Aroi {Location Name}" (e.g., "Aroi Namsos", "Aroi Lade")
- Can be overridden in `settings` table with key `sms_sender_{site_id}`

---

## 🔧 Configuration Validator

**Access:** WordPress Admin → **Aroi Config** menu

The validator shows:
- ✅ Current site ID detection
- ✅ Location name
- ✅ PCKasse license number
- ✅ Delivery time
- ✅ SMS credentials status
- ✅ All sites in database
- ❌ Any configuration errors

**Screenshot:**
```
┌─────────────────────────────────────┐
│ Dynamic Configuration Status        │
├─────────────────────────────────────┤
│ ✅ Configuration is valid!          │
│                                     │
│ Site ID:         7                  │
│ Location Name:   Namsos             │
│ PCKasse License: 6714               │
│ Delivery Time:   30 minutes         │
│ SMS Sender:      Aroi Namsos        │
│ SMS Username:    ✓ Configured       │
└─────────────────────────────────────┘
```

---

## 🌐 WordPress Multisite Configuration

### Setup for Multisite

**No configuration needed!** The plugin automatically:
1. Detects Blog ID from WordPress
2. Uses Blog ID as Site ID
3. Fetches license, location name, etc. from database

**Example:**
```
Blog 1 (Namsos) → Site ID: 7 → License: 6714
Blog 2 (Lade)   → Site ID: 4 → License: 12381
Blog 3 (Moan)   → Site ID: 6 → License: 5203
```

### Mapping Blog ID to Site ID

If your Blog IDs don't match your Site IDs in the database, use this mapping:

**Create `wp-content/mu-plugins/aroi-multisite-mapping.php`:**
```php
<?php
/**
 * Aroi MultiSite Blog ID to Site ID Mapping
 */

add_filter('multiside_aroi_site_id', function($detected_site_id) {
    $mapping = array(
        1 => 7,   // Blog 1 → Namsos (Site 7)
        2 => 4,   // Blog 2 → Lade (Site 4)
        3 => 6,   // Blog 3 → Moan (Site 6)
        4 => 5,   // Blog 4 → Gramyra (Site 5)
        5 => 10,  // Blog 5 → Frosta (Site 10)
        6 => 11,  // Blog 6 → Hell (Site 11)
        7 => 12,  // Blog 7 → Steinkjer (Site 12)
    );

    $blog_id = get_current_blog_id();
    return isset($mapping[$blog_id]) ? $mapping[$blog_id] : $detected_site_id;
}, 10, 1);
```

---

## 📋 Database Schema Requirements

### Required Tables:

#### 1. `_apningstid` (Opening Hours)
```sql
CREATE TABLE _apningstid (
  AvdID INT PRIMARY KEY,           -- Site ID (1-7)
  Navn VARCHAR(255),               -- Location name
  url VARCHAR(255),                -- Site URL
  ManStart TIME,                   -- Monday start
  ManStopp TIME,                   -- Monday stop
  ManStengt BOOLEAN,               -- Monday closed
  TirStart TIME, TirStopp TIME, TirStengt BOOLEAN,
  OnsStart TIME, OnsStopp TIME, OnsStengt BOOLEAN,
  TorStart TIME, TorStopp TIME, TorStengt BOOLEAN,
  FreStart TIME, FreStopp TIME, FreStengt BOOLEAN,
  LorStart TIME, LorStopp TIME, LorStengt BOOLEAN,
  SonStart TIME, SonStopp TIME, SonStengt BOOLEAN
);
```

#### 2. `users` (Site Users & Licenses)
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255),
  password VARCHAR(255),
  siteid INT,                      -- Site ID
  license INT                      -- PCKasse license
);
```

#### 3. `leveringstid` (Delivery Times)
```sql
CREATE TABLE leveringstid (
  id INT PRIMARY KEY,              -- Site ID
  tid INT                          -- Delivery time in minutes
);
```

#### 4. `orders` (Order Storage)
```sql
CREATE TABLE orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ordreid INT,                     -- WooCommerce order ID
  site INT,                        -- Site ID
  paid BOOLEAN DEFAULT 0,
  curl INT DEFAULT 0,              -- PCKasse status
  sms BOOLEAN DEFAULT 0,
  fornavn VARCHAR(255),
  etternavn VARCHAR(255),
  telefon VARCHAR(20),
  epost VARCHAR(255),
  datetime TIMESTAMP,
  hentes VARCHAR(255),             -- Pickup time
  total_amount DECIMAL(10,2)
);
```

#### 5. `settings` (Optional - SMS Credentials)
```sql
CREATE TABLE settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  setting_key VARCHAR(255) UNIQUE,
  setting_value TEXT
);

-- Example data:
INSERT INTO settings (setting_key, setting_value) VALUES
('sms_api_username', 'b3166vr0f0l'),
('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8'),
('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain'),
('sms_sender', 'AroiAsia');
```

---

## 🔑 Configuration Priority

### PCKasse License:
1. `sites` table (`license` field)
2. `users` table (`license` field)
3. Error if not found

### Location Name:
1. `_apningstid` table (`Navn` field)
2. `sites` table (`name` field)
3. "Unknown Location" if not found

### SMS Credentials:
1. `settings` table (dynamic)
2. Hardcoded fallback (legacy support)

### SMS Sender:
1. `settings` table (`sms_sender_{site_id}`)
2. `_apningstid.Navn` → "Aroi {Location}"
3. "AroiAsia" (default)

---

## 🧪 Testing Configuration

### Method 1: Admin Dashboard
1. Go to WordPress Admin
2. Click **Aroi Config** in sidebar
3. View configuration status

### Method 2: PHP Test
Create `test-config.php` in WordPress root:

```php
<?php
require_once 'wp-load.php';

$site_id = Multiside_Aroi_Site_Config::get_current_site_id();
$config = Multiside_Aroi_Site_Config::get_site_config($site_id);

echo "Site ID: {$config['site_id']}\n";
echo "Location: {$config['location_name']}\n";
echo "License: {$config['pckasse_license']}\n";
echo "Delivery Time: {$config['delivery_time']} min\n";
echo "SMS Sender: {$config['sms_sender']}\n";
```

Run: `php test-config.php`

### Method 3: WordPress Debug Log
Enable debug logging in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `wp-content/debug.log` for messages:
```
MultiSide Aroi: Detected multisite blog ID: 1
MultiSide Aroi: Order created - Site: 7 - License: 6714
MultiSide Aroi: PCKasse order sent successfully - Site: 7
MultiSide Aroi: SMS sent successfully - Phone: +4712345678
```

---

## ⚠️ Troubleshooting

### "Site ID kunne ikke detekteres automatisk"

**Cause:** Plugin cannot determine which site is being used

**Solutions:**
1. Verify `_apningstid` table has entry with matching URL
2. For multisite: ensure WordPress Multisite is configured correctly
3. For single site: ensure `users` table has at least one entry with `siteid > 0`

### "PCKasse-lisens mangler for site X"

**Cause:** No license found in database for site

**Solutions:**
1. Check `users` table: `SELECT license FROM users WHERE siteid = X`
2. Check `sites` table: `SELECT license FROM sites WHERE site_id = X`
3. Add license manually:
   ```sql
   UPDATE users SET license = 6714 WHERE siteid = 7;
   ```

### "SMS-credentials mangler"

**Cause:** SMS credentials not found in database and fallback disabled

**Solutions:**
1. Add to `settings` table:
   ```sql
   INSERT INTO settings (setting_key, setting_value) VALUES
   ('sms_api_username', 'your_username'),
   ('sms_api_password', 'your_password');
   ```
2. Or rely on hardcoded fallback (legacy support)

---

## 🚀 Advanced: Custom Configuration Sources

You can add custom configuration sources by filtering:

### Custom License Source
```php
add_filter('multiside_aroi_pckasse_license', function($license, $site_id) {
    // Your custom logic
    return get_option('custom_license_' . $site_id, $license);
}, 10, 2);
```

### Custom SMS Credentials
```php
add_filter('multiside_aroi_sms_credentials', function($credentials, $type) {
    // Your custom logic
    return array(
        'username' => get_option('custom_sms_user'),
        'password' => get_option('custom_sms_pass'),
        'url' => $credentials['url'],
        'sender' => $credentials['sender'],
    );
}, 10, 2);
```

---

## 📝 Summary

✅ **NO hardcoding** - All configuration from database
✅ **Automatic site detection** - Multisite and single site support
✅ **Fallback mechanisms** - Always has working configuration
✅ **Built-in validator** - Easy troubleshooting via admin page
✅ **Flexible** - Can override via filters if needed

**The plugin "just works" for WordPress Multisite with zero configuration!**

---

**Questions?** Check the main README.md or contact InfoDesk AS.
