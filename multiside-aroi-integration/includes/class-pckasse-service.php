<?php
/**
 * PCKasse POS Integration Service
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PCKasse service class for POS integration
 */
class Multiside_Aroi_PCKasse_Service {

    /**
     * PCKasse API base URL
     */
    const API_BASE_URL = 'https://min.pckasse.no/QueueGetOrders.aspx';

    /**
     * License mapping by site ID
     */
    private static $license_map = array(
        7  => 6714,   // Namsos
        4  => 12381,  // Lade
        6  => 5203,   // Moan
        5  => 6715,   // Gramyra
        10 => 14780,  // Frosta
        11 => null,   // Hell (no license)
        12 => 30221,  // Steinkjer
        13 => 30221,  // Steinkjer (legacy ID)
        15 => 14946,  // Malvik
    );

    /**
     * Send order to PCKasse POS system
     *
     * @param int $site_id Site/location ID
     * @return array Response with 'success' and 'http_code'
     */
    public static function send_order($site_id) {
        // Get license for site
        $license = self::get_license($site_id);

        if (!$license) {
            error_log(sprintf(
                'MultiSide Aroi: No PCKasse license found for site %d',
                $site_id
            ));
            return array(
                'success' => false,
                'http_code' => 0,
                'message' => 'No PCKasse license configured for this location'
            );
        }

        // Build API URL
        $url = self::API_BASE_URL . '?licenceno=' . $license;

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $success = in_array($http_code, array(200, 201));

        if ($success) {
            error_log(sprintf(
                'MultiSide Aroi: PCKasse order sent successfully - Site: %d - License: %d - HTTP: %d',
                $site_id,
                $license,
                $http_code
            ));
        } else {
            error_log(sprintf(
                'MultiSide Aroi: PCKasse order send failed - Site: %d - License: %d - HTTP: %d - Error: %s',
                $site_id,
                $license,
                $http_code,
                $curl_error
            ));
        }

        return array(
            'success' => $success,
            'http_code' => $http_code,
            'message' => $success ? 'Order sent to PCKasse' : 'Failed to send order to PCKasse',
            'response' => $response
        );
    }

    /**
     * Get PCKasse license for site
     *
     * @param int $site_id Site/location ID
     * @return int|null License number or null
     */
    public static function get_license($site_id) {
        if (isset(self::$license_map[$site_id])) {
            return self::$license_map[$site_id];
        }

        // Try to get from database (users table)
        $sql = sprintf(
            "SELECT license FROM users WHERE siteid = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['license']);
        }

        return null;
    }

    /**
     * Get location name by site ID
     *
     * @param int $site_id Site/location ID
     * @return string Location name
     */
    public static function get_location_name($site_id) {
        $names = array(
            7  => 'Namsos',
            4  => 'Lade',
            6  => 'Moan',
            5  => 'Gramyra',
            10 => 'Frosta',
            11 => 'Hell',
            12 => 'Steinkjer',
            13 => 'Steinkjer',
            15 => 'Malvik',
        );

        return isset($names[$site_id]) ? $names[$site_id] : 'Unknown';
    }

    /**
     * Verify PCKasse connection for a site
     *
     * @param int $site_id Site/location ID
     * @return bool Connection successful
     */
    public static function verify_connection($site_id) {
        $result = self::send_order($site_id);
        return $result['success'];
    }
}
