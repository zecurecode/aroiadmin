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
 * ALL configuration is fetched DYNAMICALLY from database - NO hardcoding!
 */
class Multiside_Aroi_PCKasse_Service {

    /**
     * PCKasse API base URL
     */
    const API_BASE_URL = 'https://min.pckasse.no/QueueGetOrders.aspx';

    /**
     * Send order to PCKasse POS system
     * License is fetched DYNAMICALLY from database based on site_id
     *
     * @param int $site_id Site/location ID
     * @return array Response with 'success' and 'http_code'
     */
    public static function send_order($site_id) {
        // Get license DYNAMICALLY from database
        $license = Multiside_Aroi_Site_Config::get_pckasse_license($site_id);

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
     * Get PCKasse license for site (DYNAMIC - delegates to Site_Config)
     *
     * @param int $site_id Site/location ID
     * @return int|null License number or null
     */
    public static function get_license($site_id) {
        return Multiside_Aroi_Site_Config::get_pckasse_license($site_id);
    }

    /**
     * Get location name by site ID (DYNAMIC - delegates to Site_Config)
     *
     * @param int $site_id Site/location ID
     * @return string Location name
     */
    public static function get_location_name($site_id) {
        return Multiside_Aroi_Site_Config::get_location_name($site_id);
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
