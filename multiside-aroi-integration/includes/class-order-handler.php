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

        // Get site ID from current blog or meta
        $site_id = $this->get_site_id();

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

        // Get pickup time if set
        $pickup_time = $order->get_meta('hentes_kl');
        if ($pickup_time) {
            $data['hentes'] = $pickup_time;
        }

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

        // Get site ID
        $site_id = $this->get_site_id();

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

        // STEP 2: Send SMS to customer (REQUIRED!)
        $order_data = $this->get_order_from_db($order_id, $site_id);

        if ($order_data && $order_data['telefon']) {
            $sms_sent = Multiside_Aroi_SMS_Service::send_order_confirmation(
                $order_id,
                $order_data['telefon']
            );

            if ($sms_sent) {
                // Mark SMS as sent
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

        error_log(sprintf(
            'MultiSide Aroi: Payment completion processing COMPLETE for order %d',
            $order_id
        ));
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
     * Get current site ID
     *
     * @return int Site ID
     */
    private function get_site_id() {
        // For multisite, use blog ID
        if (is_multisite()) {
            return get_current_blog_id();
        }

        // For single site, try to determine from URL or settings
        // You can set this via a constant in wp-config.php:
        // define('AROI_SITE_ID', 7);
        if (defined('AROI_SITE_ID')) {
            return AROI_SITE_ID;
        }

        // Default to 7 (Namsos) if not specified
        return 7;
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
        $site_id = $this->get_site_id();
        $order_data = $this->get_order_from_db($order_id, $site_id);

        if (!$order_data) {
            return;
        }

        ?>
        <div class="aroi-order-meta">
            <h3>Aroi Order Status</h3>
            <p>
                <strong>Site:</strong> <?php echo esc_html(Multiside_Aroi_PCKasse_Service::get_location_name($site_id)); ?><br>
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
