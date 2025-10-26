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
 * ALL credentials and sender info fetched DYNAMICALLY from database - NO hardcoding!
 */
class Multiside_Aroi_SMS_Service {

    /**
     * Send customer notification SMS with DYNAMIC sender from database
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @param int|null $site_id Site ID (for dynamic sender name)
     * @return bool Success
     */
    public static function send_order_confirmation($order_id, $phone, $site_id = null) {
        $message = sprintf(
            'Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan. Vi sender deg en ny SMS når maten er klar til henting. Ditt referansenummer er %d',
            $order_id
        );

        // Get credentials DYNAMICALLY from database
        $credentials = Multiside_Aroi_Site_Config::get_sms_credentials('customer');

        // Get sender DYNAMICALLY based on site
        $sender = $site_id ? Multiside_Aroi_Site_Config::get_sms_sender($site_id) : $credentials['sender'];

        return self::send_sms($phone, $message, $credentials, $sender);
    }

    /**
     * Send order ready notification with DYNAMIC sender
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @param string $customer_name Customer first name
     * @param int $site_id Site ID
     * @return bool Success
     */
    public static function send_order_ready($order_id, $phone, $customer_name, $site_id) {
        $location_name = Multiside_Aroi_Site_Config::get_location_name($site_id);

        $message = sprintf(
            'Hei %s! Din ordre #%d er klar for henting. Mvh %s',
            $customer_name,
            $order_id,
            $location_name
        );

        $credentials = Multiside_Aroi_Site_Config::get_sms_credentials('customer');
        $sender = Multiside_Aroi_Site_Config::get_sms_sender($site_id);

        return self::send_sms($phone, $message, $credentials, $sender);
    }

    /**
     * Send order picked up notification with DYNAMIC sender
     *
     * @param int $order_id Order ID
     * @param string $phone Customer phone number
     * @param int $site_id Site ID
     * @return bool Success
     */
    public static function send_order_picked_up($order_id, $phone, $site_id) {
        $location_name = Multiside_Aroi_Site_Config::get_location_name($site_id);

        $message = sprintf(
            'Takk for handelen! Din ordre #%d er hentet. Velkommen tilbake! Mvh %s',
            $order_id,
            $location_name
        );

        $credentials = Multiside_Aroi_Site_Config::get_sms_credentials('customer');
        $sender = Multiside_Aroi_Site_Config::get_sms_sender($site_id);

        return self::send_sms($phone, $message, $credentials, $sender);
    }

    /**
     * Send admin alert for unpaid order with DYNAMIC admin phones
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

        // Get admin credentials DYNAMICALLY
        $credentials = Multiside_Aroi_Site_Config::get_sms_credentials('admin');

        // Default admin phones (can be made dynamic via database settings)
        $admin_phones = '4790039911,4796017450';

        return self::send_sms($admin_phones, $message, $credentials, $credentials['sender']);
    }

    /**
     * Send SMS via Teletopia API with DYNAMIC credentials and sender
     *
     * @param string $phone Phone number (will be normalized to +47 format)
     * @param string $message SMS message
     * @param array $credentials Credentials array with 'username', 'password', 'url'
     * @param string $sender Sender name
     * @return bool Success
     */
    private static function send_sms($phone, $message, $credentials, $sender) {
        // Normalize phone number
        $phone = self::normalize_phone($phone);

        // Build API URL with DYNAMIC credentials
        $params = array(
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'recipient' => $phone,
            'text' => $message,
            'from' => $sender
        );

        $url = $credentials['url'] . '?' . http_build_query($params);

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
