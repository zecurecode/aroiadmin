<?php
/**
 * Site Configuration - Dynamic Database Loader
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site configuration class - fetches all config from database dynamically
 */
class Multiside_Aroi_Site_Config {

    /**
     * Cache for site configurations
     */
    private static $config_cache = array();

    /**
     * Get current site ID dynamically
     *
     * @return int|false Site ID or false on failure
     */
    public static function get_current_site_id() {
        // Priority 1: WordPress Multisite
        if (is_multisite()) {
            $blog_id = get_current_blog_id();
            error_log(sprintf('MultiSide Aroi: Detected multisite blog ID: %d', $blog_id));
            return $blog_id;
        }

        // Priority 2: Try to detect from URL/domain in database
        $current_url = home_url();
        $site_id = self::get_site_id_by_url($current_url);

        if ($site_id) {
            error_log(sprintf('MultiSide Aroi: Detected site ID from URL: %d', $site_id));
            return $site_id;
        }

        // Priority 3: Try from _apningstid table by matching site URL
        $site_id = self::get_site_id_from_apningstid($current_url);

        if ($site_id) {
            error_log(sprintf('MultiSide Aroi: Detected site ID from _apningstid: %d', $site_id));
            return $site_id;
        }

        // Priority 4: Check if single user in users table (single site setup)
        $site_id = self::get_single_site_id();

        if ($site_id) {
            error_log(sprintf('MultiSide Aroi: Using single site ID: %d', $site_id));
            return $site_id;
        }

        error_log('MultiSide Aroi: WARNING - Could not detect site ID automatically');
        return false;
    }

    /**
     * Get site ID by URL from sites table
     *
     * @param string $url Site URL
     * @return int|false Site ID or false
     */
    private static function get_site_id_by_url($url) {
        // Check if sites table exists
        $sql = "SHOW TABLES LIKE 'sites'";
        $result = Multiside_Aroi_Database::query($sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            return false;
        }

        // Try to find site by URL
        $url_clean = rtrim($url, '/');
        $sql = sprintf(
            "SELECT site_id FROM sites WHERE url LIKE '%%%s%%' AND active = 1 LIMIT 1",
            Multiside_Aroi_Database::escape($url_clean)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['site_id']);
        }

        return false;
    }

    /**
     * Get site ID from _apningstid table by URL
     *
     * @param string $url Site URL
     * @return int|false Site ID or false
     */
    private static function get_site_id_from_apningstid($url) {
        $url_clean = rtrim($url, '/');

        $sql = sprintf(
            "SELECT AvdID FROM _apningstid WHERE url LIKE '%%%s%%' LIMIT 1",
            Multiside_Aroi_Database::escape($url_clean)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['AvdID']);
        }

        return false;
    }

    /**
     * Get site ID for single site setup
     *
     * @return int|false Site ID or false
     */
    private static function get_single_site_id() {
        // If only one user in users table, use their siteid
        $sql = "SELECT siteid FROM users WHERE siteid > 0 AND siteid != 0 LIMIT 1";
        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['siteid']);
        }

        return false;
    }

    /**
     * Get PCKasse license for site (DYNAMIC from database)
     *
     * @param int $site_id Site ID
     * @return int|false License number or false
     */
    public static function get_pckasse_license($site_id) {
        if (!$site_id) {
            return false;
        }

        // Check cache
        if (isset(self::$config_cache[$site_id]['license'])) {
            return self::$config_cache[$site_id]['license'];
        }

        // Priority 1: Try sites table
        $license = self::get_license_from_sites_table($site_id);
        if ($license) {
            self::$config_cache[$site_id]['license'] = $license;
            return $license;
        }

        // Priority 2: Try users table
        $license = self::get_license_from_users_table($site_id);
        if ($license) {
            self::$config_cache[$site_id]['license'] = $license;
            return $license;
        }

        error_log(sprintf('MultiSide Aroi: No PCKasse license found for site %d', $site_id));
        return false;
    }

    /**
     * Get license from sites table
     *
     * @param int $site_id Site ID
     * @return int|false
     */
    private static function get_license_from_sites_table($site_id) {
        $sql = sprintf(
            "SELECT license FROM sites WHERE site_id = %d AND active = 1 LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $license = intval($row['license']);
            if ($license > 0) {
                return $license;
            }
        }

        return false;
    }

    /**
     * Get license from users table
     *
     * @param int $site_id Site ID
     * @return int|false
     */
    private static function get_license_from_users_table($site_id) {
        $sql = sprintf(
            "SELECT license FROM users WHERE siteid = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $license = intval($row['license']);
            if ($license > 0) {
                return $license;
            }
        }

        return false;
    }

    /**
     * Get location name dynamically from database
     *
     * @param int $site_id Site ID
     * @return string Location name
     */
    public static function get_location_name($site_id) {
        if (!$site_id) {
            return 'Unknown';
        }

        // Check cache
        if (isset(self::$config_cache[$site_id]['name'])) {
            return self::$config_cache[$site_id]['name'];
        }

        // Priority 1: _apningstid table
        $sql = sprintf(
            "SELECT Navn FROM _apningstid WHERE AvdID = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $name = $row['Navn'];
            self::$config_cache[$site_id]['name'] = $name;
            return $name;
        }

        // Priority 2: sites table
        $sql = sprintf(
            "SELECT name FROM sites WHERE site_id = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $name = $row['name'];
            self::$config_cache[$site_id]['name'] = $name;
            return $name;
        }

        return 'Unknown Location';
    }

    /**
     * Get SMS credentials dynamically from database
     *
     * @param string $type 'customer' or 'admin'
     * @return array Credentials array
     */
    public static function get_sms_credentials($type = 'customer') {
        // Try to get from settings table
        $sql = sprintf(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sms_%%' OR setting_key LIKE 'teletopia_%%'"
        );

        $result = Multiside_Aroi_Database::query($sql);

        $credentials = array();

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $credentials[$row['setting_key']] = $row['setting_value'];
            }
        }

        // If found in database, use those
        if (!empty($credentials)) {
            return array(
                'username' => $credentials['sms_api_username'] ?? $credentials['teletopia_username'] ?? '',
                'password' => $credentials['sms_api_password'] ?? $credentials['teletopia_password'] ?? '',
                'sender' => $credentials['sms_sender'] ?? 'AroiAsia',
                'url' => $credentials['sms_api_url'] ?? 'https://api1.teletopiasms.no/gateway/v3/plain',
            );
        }

        // Fallback to hardcoded credentials (legacy support)
        if ($type === 'admin') {
            return array(
                'username' => 'p3166eu720i',
                'password' => 'Nvn4xh8HADL5YvInFI4GLlhM',
                'sender' => 'AroiAsia',
                'url' => 'https://api1.teletopiasms.no/gateway/v3/plain',
            );
        }

        return array(
            'username' => 'b3166vr0f0l',
            'password' => '2tm2bxuIo2AixNELhXhwCdP8',
            'sender' => 'AroiAsia',
            'url' => 'https://api1.teletopiasms.no/gateway/v3/plain',
        );
    }

    /**
     * Get SMS sender name for site (dynamic from database)
     *
     * @param int $site_id Site ID
     * @return string Sender name
     */
    public static function get_sms_sender($site_id) {
        // Try to get site-specific sender from database
        $location_name = self::get_location_name($site_id);

        // Check if there's a custom sender in settings
        $sql = sprintf(
            "SELECT setting_value FROM settings WHERE setting_key = 'sms_sender_%d' LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['setting_value'];
        }

        // Default: Use location name or AroiAsia
        return !empty($location_name) && $location_name !== 'Unknown Location'
            ? 'Aroi ' . $location_name
            : 'AroiAsia';
    }

    /**
     * Get all site IDs from database
     *
     * @return array Array of site IDs
     */
    public static function get_all_site_ids() {
        $site_ids = array();

        // Get from _apningstid table
        $sql = "SELECT AvdID FROM _apningstid WHERE AvdID > 0 ORDER BY AvdID";
        $result = Multiside_Aroi_Database::query($sql);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $site_ids[] = intval($row['AvdID']);
            }
        }

        return $site_ids;
    }

    /**
     * Get delivery time for site (dynamic from database)
     *
     * @param int $site_id Site ID
     * @return int Delivery time in minutes
     */
    public static function get_delivery_time($site_id) {
        if (!$site_id) {
            return 30; // Default
        }

        // Try leveringstid table
        $sql = sprintf(
            "SELECT tid FROM leveringstid WHERE id = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['tid']);
        }

        // Default to 30 minutes
        return 30;
    }

    /**
     * Get complete site configuration
     *
     * @param int|null $site_id Site ID (null = auto-detect)
     * @return array Configuration array
     */
    public static function get_site_config($site_id = null) {
        if ($site_id === null) {
            $site_id = self::get_current_site_id();
        }

        if (!$site_id) {
            return array(
                'site_id' => false,
                'error' => 'Could not detect site ID',
            );
        }

        return array(
            'site_id' => $site_id,
            'location_name' => self::get_location_name($site_id),
            'pckasse_license' => self::get_pckasse_license($site_id),
            'delivery_time' => self::get_delivery_time($site_id),
            'sms_sender' => self::get_sms_sender($site_id),
            'sms_credentials' => self::get_sms_credentials('customer'),
        );
    }

    /**
     * Validate site configuration
     *
     * @param int|null $site_id Site ID
     * @return array Validation result
     */
    public static function validate_config($site_id = null) {
        if ($site_id === null) {
            $site_id = self::get_current_site_id();
        }

        $errors = array();
        $warnings = array();

        if (!$site_id) {
            $errors[] = 'Site ID kunne ikke detekteres automatisk';
            return array('valid' => false, 'errors' => $errors, 'warnings' => $warnings);
        }

        $license = self::get_pckasse_license($site_id);
        if (!$license) {
            $errors[] = sprintf('PCKasse-lisens mangler for site %d', $site_id);
        }

        $location_name = self::get_location_name($site_id);
        if ($location_name === 'Unknown Location') {
            $warnings[] = sprintf('Lokasjonsnavn ikke funnet for site %d', $site_id);
        }

        $sms_creds = self::get_sms_credentials();
        if (empty($sms_creds['username']) || empty($sms_creds['password'])) {
            $errors[] = 'SMS-credentials mangler';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'config' => self::get_site_config($site_id),
        );
    }

    /**
     * Clear configuration cache
     */
    public static function clear_cache() {
        self::$config_cache = array();
        error_log('MultiSide Aroi: Configuration cache cleared');
    }
}
