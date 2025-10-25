<?php
/**
 * SMS Service Handler
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SMS Service class for Teletopia integration
 */
class Multiside_Aroi_SMS_Service {

    /**
     * Teletopia API credentials
     */
    const API_URL = 'https://api1.teletopiasms.no/gateway/v3/plain';
    const API_USERNAME = 'b3166vr0f0l';
    const API_PASSWORD = '2tm2bxuIo2AixNELhXhwCdP8';
    const SENDER_NAME = 'AroiAsia';

    /**
     * Admin alert credentials
     */
    const ADMIN_USERNAME = 'p3166eu720i';
    const ADMIN_PASSWORD = 'Nvn4xh8HADL5YvInFI4GLlhM';
    const ADMIN_PHONES = '4790039911,4796017450';

    /**
     * Send customer notification SMS
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @return bool Success
     */
    public static function send_order_confirmation($order_id, $phone) {
        $message = sprintf(
            'Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan. Vi sender deg en ny SMS når maten er klar til henting. Ditt referansenummer er %d',
            $order_id
        );

        return self::send_sms($phone, $message, self::API_USERNAME, self::API_PASSWORD);
    }

    /**
     * Send order ready notification
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @param string $customer_name Customer first name
     * @param string $location_name Location name
     * @return bool Success
     */
    public static function send_order_ready($order_id, $phone, $customer_name, $location_name) {
        $message = sprintf(
            'Hei %s! Din ordre #%d er klar for henting. Mvh %s',
            $customer_name,
            $order_id,
            $location_name
        );

        return self::send_sms($phone, $message, self::API_USERNAME, self::API_PASSWORD);
    }

    /**
     * Send order picked up notification
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @param string $location_name Location name
     * @return bool Success
     */
    public static function send_order_picked_up($order_id, $phone, $location_name) {
        $message = sprintf(
            'Takk for handelen! Din ordre #%d er hentet. Velkommen tilbake! Mvh %s',
            $order_id,
            $location_name
        );

        return self::send_sms($phone, $message, self::API_USERNAME, self::API_PASSWORD);
    }

    /**
     * Send admin alert for unpaid order
     *
     * @param int $order_id Order ID
     * @param int $site_id Site/location ID
     * @param int $minutes Minutes since order created
     * @return bool Success
     */
    public static function send_admin_alert($order_id, $site_id, $minutes) {
        $message = sprintf(
            'Aroi ordreid %d (vogn %d) har ikke blitt betalt på %d minutter!',
            $order_id,
            $site_id,
            $minutes
        );

        return self::send_sms(self::ADMIN_PHONES, $message, self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
    }

    /**
     * Send SMS via Teletopia API
     *
     * @param string $phone Phone number (will be normalized to +47 format)
     * @param string $message SMS message
     * @param string $username API username
     * @param string $password API password
     * @return bool Success
     */
    private static function send_sms($phone, $message, $username, $password) {
        // Normalize phone number
        $phone = self::normalize_phone($phone);

        // Build API URL
        $params = array(
            'username' => $username,
            'password' => $password,
            'recipient' => $phone,
            'text' => $message,
            'from' => self::SENDER_NAME
        );

        $url = self::API_URL . '?' . http_build_query($params);

        // Send via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200) {
            error_log(sprintf(
                'MultiSide Aroi: SMS send failed - HTTP %d - Phone: %s - Error: %s',
                $http_code,
                $phone,
                $curl_error
            ));
            return false;
        }

        error_log(sprintf(
            'MultiSide Aroi: SMS sent successfully - Phone: %s - Message: %s',
            $phone,
            substr($message, 0, 50) . '...'
        ));

        return true;
    }

    /**
     * Normalize phone number to +47 format
     *
     * @param string $phone Phone number in various formats
     * @return string Normalized phone number
     */
    private static function normalize_phone($phone) {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Already has +47 - return as-is
        if (substr($phone, 0, 3) === '+47') {
            return $phone;
        }

        // 0047 format - convert to +47
        if (substr($phone, 0, 4) === '0047') {
            return '+47' . substr($phone, 4);
        }

        // 47 format (no +) - add the +
        if (substr($phone, 0, 2) === '47' && strlen($phone) >= 10) {
            return '+' . $phone;
        }

        // 8-digit Norwegian number - add +47
        if (strlen($phone) === 8 && ctype_digit($phone)) {
            return '+47' . $phone;
        }

        // Return unchanged for international numbers
        return $phone;
    }
}
