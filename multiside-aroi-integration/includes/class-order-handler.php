<?php
/**
 * Order Handler - Main WooCommerce Integration
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order handler class
 */
class Multiside_Aroi_Order_Handler {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Hook into WooCommerce order creation
        add_action('woocommerce_new_order', array($this, 'on_order_created'), 1, 1);

        // Hook into checkout meta update to capture pickup time (runs AFTER checkout fields are saved)
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_pickup_time_in_db'), 20, 1);

        // Hook into payment completion - CRITICAL: This triggers PCKasse and SMS
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'), 1, 1);

        // Admin order meta display
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_meta'), 10, 1);
    }

    /**
     * Handle new order creation (UNPAID)
     *
     * @param int $order_id WooCommerce order ID
     */
    public function on_order_created($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Get site ID DYNAMICALLY (multisite-aware)
        $site_id = Multiside_Aroi_Site_Config::get_current_site_id();

        if (!$site_id) {
            error_log('MultiSide Aroi: ERROR - Cannot create order, site ID not detected');
            return;
        }

        // Extract order data
        $data = array(
            'fornavn' => $order->get_billing_first_name(),
            'etternavn' => $order->get_billing_last_name(),
            'telefon' => $this->normalize_phone($order->get_billing_phone()),
            'epost' => $order->get_billing_email(),
            'ordreid' => $order_id,
            'ordrestatus' => 0,  // Pending
            'site' => $site_id,
            'paid' => 0,         // UNPAID - critical!
            'curl' => 0,         // Not sent to PCKasse yet
            'sms' => 0,          // SMS not sent yet
            'datetime' => current_time('mysql'),
            'wcstatus' => $order->get_status(),
            'paymentmethod' => $order->get_payment_method(),
            'total_amount' => $order->get_total(),
        );

        // Get pickup time if set - with detailed debugging
        $pickup_time = $order->get_meta('hentes_kl');

        // Also check all meta data to see what's available
        $all_meta = $order->get_meta_data();
        $meta_keys = array();
        foreach ($all_meta as $meta) {
            $meta_keys[] = $meta->key;
        }

        error_log(sprintf(
            'MultiSide Aroi: Order %d meta keys available: %s',
            $order_id,
            implode(', ', $meta_keys)
        ));

        if ($pickup_time) {
            $data['hentes'] = $pickup_time;
            error_log(sprintf(
                'MultiSide Aroi: Pickup time found for order %d: "%s" (will be saved to hentes column)',
                $order_id,
                $pickup_time
            ));
        } else {
            error_log(sprintf(
                'MultiSide Aroi: WARNING - No pickup time (hentes_kl) found for order %d - checking for _hentes_kl',
                $order_id
            ));

            // Try with underscore prefix (WordPress sometimes stores with prefix)
            $pickup_time_alt = $order->get_meta('_hentes_kl');
            if ($pickup_time_alt) {
                $data['hentes'] = $pickup_time_alt;
                error_log(sprintf(
                    'MultiSide Aroi: Found pickup time with underscore prefix: "%s"',
                    $pickup_time_alt
                ));
            } else {
                error_log('MultiSide Aroi: No pickup time found with or without underscore');
            }
        }

        // Log the data array before insert to verify hentes is included
        error_log(sprintf(
            'MultiSide Aroi: About to insert order %d with data: %s',
            $order_id,
            json_encode(array_intersect_key($data, array_flip(['ordreid', 'site', 'paid', 'hentes'])))
        ));

        // Insert into orders table
        $insert_id = Multiside_Aroi_Database::insert('orders', $data);

        if ($insert_id) {
            error_log(sprintf(
                'MultiSide Aroi: Order created - WC Order: %d - DB ID: %d - Site: %d - Customer: %s %s',
                $order_id,
                $insert_id,
                $site_id,
                $data['fornavn'],
                $data['etternavn']
            ));
        } else {
            error_log(sprintf(
                'MultiSide Aroi: Failed to create order in database - WC Order: %d',
                $order_id
            ));
        }
    }

    /**
     * Update pickup time in database after checkout meta is saved
     * This runs AFTER woocommerce_checkout_update_order_meta saves the hentes_kl field
     *
     * @param int $order_id WooCommerce order ID
     */
    public function update_pickup_time_in_db($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Get site ID
        $site_id = Multiside_Aroi_Site_Config::get_current_site_id();
        if (!$site_id) {
            return;
        }

        // Get pickup time from order meta (should be available now)
        $pickup_time = $order->get_meta('hentes_kl');

        if ($pickup_time) {
            // Update the hentes field in the database
            $updated = Multiside_Aroi_Database::update(
                'orders',
                array('hentes' => $pickup_time),
                array('ordreid' => $order_id, 'site' => $site_id)
            );

            if ($updated) {
                error_log(sprintf(
                    'MultiSide Aroi: Pickup time updated in DB - Order: %d - Site: %d - Time: %s',
                    $order_id,
                    $site_id,
                    $pickup_time
                ));
            } else {
                error_log(sprintf(
                    'MultiSide Aroi: Failed to update pickup time in DB - Order: %d - Site: %d',
                    $order_id,
                    $site_id
                ));
            }
        } else {
            error_log(sprintf(
                'MultiSide Aroi: No pickup time found to update - Order: %d - Site: %d',
                $order_id,
                $site_id
            ));
        }
    }

    /**
     * Handle payment completion - CRITICAL FUNCTION
     * This is where we:
     * 1. Mark order as PAID
     * 2. Send order to PCKasse POS
     * 3. Send SMS to customer
     *
     * @param int $order_id WooCommerce order ID
     */
    public function on_payment_complete($order_id) {
        if (!$order_id) {
            return;
        }

        error_log(sprintf(
            'MultiSide Aroi: Payment complete triggered for order %d',
            $order_id
        ));

        // Get site ID DYNAMICALLY (multisite-aware)
        $site_id = Multiside_Aroi_Site_Config::get_current_site_id();

        if (!$site_id) {
            error_log('MultiSide Aroi: ERROR - Cannot process payment, site ID not detected');
            return;
        }

        $location_name = Multiside_Aroi_Site_Config::get_location_name($site_id);

        // Update order as PAID in database
        $updated = Multiside_Aroi_Database::update(
            'orders',
            array('paid' => 1),
            array('ordreid' => $order_id, 'site' => $site_id)
        );

        if (!$updated) {
            error_log(sprintf(
                'MultiSide Aroi: Failed to mark order %d as paid',
                $order_id
            ));
            return;
        }

        error_log(sprintf(
            'MultiSide Aroi: Order %d marked as PAID - Now sending to PCKasse and SMS',
            $order_id
        ));

        // STEP 1: Send order to PCKasse POS (REQUIRED!)
        $pck_result = Multiside_Aroi_PCKasse_Service::send_order($site_id);

        if ($pck_result['success']) {
            // Update curl status with HTTP code
            Multiside_Aroi_Database::update(
                'orders',
                array(
                    'curl' => $pck_result['http_code'],
                    'curltime' => current_time('mysql')
                ),
                array('ordreid' => $order_id, 'site' => $site_id)
            );

            error_log(sprintf(
                'MultiSide Aroi: PCKasse send SUCCESS - Order: %d - HTTP: %d',
                $order_id,
                $pck_result['http_code']
            ));
        } else {
            error_log(sprintf(
                'MultiSide Aroi: PCKasse send FAILED - Order: %d - HTTP: %d',
                $order_id,
                $pck_result['http_code']
            ));
            // Don't return - still send SMS to customer
        }

        // STEP 2: Notify Laravel that order is paid so it can send "order received" SMS
        $this->notify_laravel_payment_complete($order_id, $site_id);

        error_log(sprintf(
            'MultiSide Aroi: Laravel notified of payment completion - Order: %d',
            $order_id
        ));

        // NOTE: The following SMS code is disabled. Laravel admin now sends:
        // - "Order received" SMS when order arrives in system
        // - "Order ready" SMS when status changes to ready for pickup

        /* DISABLED - SMS now handled by Laravel
        $order_data = $this->get_order_from_db($order_id, $site_id);

        if ($order_data && $order_data['telefon']) {
            $sms_sent = Multiside_Aroi_SMS_Service::send_order_confirmation(
                $order_id,
                $order_data['telefon'],
                $site_id
            );

            if ($sms_sent) {
                Multiside_Aroi_Database::update(
                    'orders',
                    array('sms' => 1),
                    array('ordreid' => $order_id, 'site' => $site_id)
                );

                error_log(sprintf(
                    'MultiSide Aroi: SMS sent SUCCESS - Order: %d - Phone: %s',
                    $order_id,
                    $order_data['telefon']
                ));
            } else {
                error_log(sprintf(
                    'MultiSide Aroi: SMS send FAILED - Order: %d - Phone: %s',
                    $order_id,
                    $order_data['telefon']
                ));
            }
        } else {
            error_log(sprintf(
                'MultiSide Aroi: No phone number found for order %d - Cannot send SMS',
                $order_id
            ));
        }
        */

        error_log(sprintf(
            'MultiSide Aroi: Payment completion processing COMPLETE for order %d',
            $order_id
        ));
    }

    /**
     * Notify Laravel admin that payment is complete
     * This triggers the "order received" SMS to customer
     *
     * @param int $order_id WooCommerce order ID
     * @param int $site_id Site ID
     */
    private function notify_laravel_payment_complete($order_id, $site_id) {
        // Get Laravel admin URL from settings or use default
        $laravel_url = defined('AROI_LARAVEL_URL') ? AROI_LARAVEL_URL : 'https://aroi.no';
        $api_endpoint = $laravel_url . '/api/v1/orders/mark-paid';

        $data = array(
            'ordreid' => $order_id,
            'site' => $site_id
        );

        $response = wp_remote_post($api_endpoint, array(
            'method' => 'POST',
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
        ));

        if (is_wp_error($response)) {
            error_log(sprintf(
                'MultiSide Aroi: Failed to notify Laravel - Order: %d - Error: %s',
                $order_id,
                $response->get_error_message()
            ));
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($http_code === 200) {
            error_log(sprintf(
                'MultiSide Aroi: Laravel notified successfully - Order: %d - Response: %s',
                $order_id,
                $body
            ));
            return true;
        } else {
            error_log(sprintf(
                'MultiSide Aroi: Laravel notification failed - Order: %d - HTTP: %d - Response: %s',
                $order_id,
                $http_code,
                $body
            ));
            return false;
        }
    }

    /**
     * Get order data from database
     *
     * @param int $order_id WooCommerce order ID
     * @param int $site_id Site ID
     * @return array|false Order data or false
     */
    private function get_order_from_db($order_id, $site_id) {
        $sql = sprintf(
            "SELECT * FROM orders WHERE ordreid = %d AND site = %d LIMIT 1",
            intval($order_id),
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return false;
    }

    /**
     * Get current site ID (DEPRECATED - use Multiside_Aroi_Site_Config::get_current_site_id())
     *
     * @return int Site ID
     * @deprecated 2.0.0 Use Multiside_Aroi_Site_Config::get_current_site_id()
     */
    private function get_site_id() {
        return Multiside_Aroi_Site_Config::get_current_site_id();
    }

    /**
     * Normalize phone number to +47 format
     *
     * @param string $phone Phone number
     * @return string Normalized phone number
     */
    private function normalize_phone($phone) {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Already has +47
        if (substr($phone, 0, 3) === '+47') {
            return $phone;
        }

        // 0047 format
        if (substr($phone, 0, 4) === '0047') {
            return '+47' . substr($phone, 4);
        }

        // 47 format (no +)
        if (substr($phone, 0, 2) === '47' && strlen($phone) >= 10) {
            return '+' . $phone;
        }

        // 8-digit Norwegian number
        if (strlen($phone) === 8 && ctype_digit($phone)) {
            return '+47' . $phone;
        }

        return $phone;
    }

    /**
     * Display order meta in admin
     *
     * @param WC_Order $order Order object
     */
    public function display_order_meta($order) {
        $order_id = $order->get_id();
        $site_id = Multiside_Aroi_Site_Config::get_current_site_id();
        $order_data = $this->get_order_from_db($order_id, $site_id);

        if (!$order_data) {
            return;
        }

        $location_name = Multiside_Aroi_Site_Config::get_location_name($site_id);
        $pckasse_license = Multiside_Aroi_Site_Config::get_pckasse_license($site_id);

        ?>
        <div class="aroi-order-meta">
            <h3>Aroi Order Status</h3>
            <p>
                <strong>Site ID:</strong> <?php echo esc_html($site_id); ?><br>
                <strong>Location:</strong> <?php echo esc_html($location_name); ?><br>
                <strong>PCKasse License:</strong> <?php echo esc_html($pckasse_license ? $pckasse_license : 'Not configured'); ?><br>
                <strong>Paid:</strong> <?php echo $order_data['paid'] ? 'Yes' : 'No'; ?><br>
                <strong>PCKasse Status:</strong> <?php echo $order_data['curl'] ? 'Sent (' . $order_data['curl'] . ')' : 'Not sent'; ?><br>
                <strong>SMS Sent:</strong> <?php echo $order_data['sms'] ? 'Yes' : 'No'; ?><br>
                <?php if ($order_data['hentes']): ?>
                    <strong>Pickup Time:</strong> <?php echo esc_html($order_data['hentes']); ?><br>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}
