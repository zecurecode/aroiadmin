<?php
/**
 * Checkout Page Manager
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checkout manager class
 */
class Multiside_Aroi_Checkout_Manager {

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
        // Add closed/open notice at top of checkout
        add_action('woocommerce_before_order_notes', array($this, 'display_opening_status_notice'), 10);

        // Add pickup time selector
        add_action('woocommerce_before_order_notes', array($this, 'display_pickup_time_selector'), 20);

        // Save pickup time to order meta
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_pickup_time'));

        // Display pickup time in admin
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_pickup_time_admin'), 10, 1);

        // Add pickup time to email
        add_filter('woocommerce_email_order_meta_keys', array($this, 'add_pickup_time_to_email'));
    }

    /**
     * Display opening status notice (red if closed, green if open)
     */
    public function display_opening_status_notice() {
        $site_id = $this->get_site_id();
        $hours = Multiside_Aroi_Opening_Hours::get_hours($site_id);

        if (!$hours) {
            return;
        }

        $is_open = Multiside_Aroi_Opening_Hours::is_open_now($site_id);

        if (!$is_open || $hours['is_closed']) {
            $next_opening = Multiside_Aroi_Opening_Hours::get_next_opening_time($site_id);
            ?>
            <div class="aroi-checkout-notice closed">
                <p class="woocommerce-info">
                    <strong>Vognen er stengt.</strong> Du kan fortsatt bestille for neste dag.
                    <?php if ($next_opening): ?>
                        Vi åpner klokken <?php echo esc_html($next_opening); ?>.
                    <?php endif; ?>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="aroi-checkout-notice open">
                <p class="woocommerce-message">
                    <strong>Åpen for henting i dag!</strong>
                    Åpent til <?php echo esc_html($hours['close_time']); ?>.
                </p>
            </div>
            <?php
        }
    }

    /**
     * Display pickup time selector
     */
    public function display_pickup_time_selector($checkout) {
        $site_id = $this->get_site_id();
        $hours = Multiside_Aroi_Opening_Hours::get_hours($site_id);
        $delivery_time = Multiside_Aroi_Opening_Hours::get_delivery_time($site_id);
        $is_open = Multiside_Aroi_Opening_Hours::is_open_now($site_id);

        // Generate time options
        $time_options = $this->generate_pickup_times($hours, $delivery_time, $is_open);

        ?>
        <div class="aroi-pickup-time-field">
            <p class="form-row form-row-wide">
                <label for="hentes_kl">
                    Når vil du hente bestillingen?
                    <span class="required">*</span>
                </label>
                <span class="woocommerce-input-wrapper">
                    <select name="hentes_kl" id="hentes_kl" class="select" required>
                        <option value="">Velg hentetid...</option>
                        <?php foreach ($time_options as $time): ?>
                            <option value="<?php echo esc_attr($time); ?>">
                                <?php echo esc_html($time); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </p>
            <p class="delivery-time-info">
                Det tar ca. <strong><?php echo esc_html($delivery_time); ?> minutter</strong> før bestillingen er klar.
            </p>
        </div>
        <?php
    }

    /**
     * Generate pickup time options
     *
     * @param array $hours Opening hours
     * @param int $delivery_time Delivery time in minutes
     * @param bool $is_open Whether location is currently open
     * @return array Time options
     */
    private function generate_pickup_times($hours, $delivery_time, $is_open) {
        $times = array();

        if (!$hours) {
            return $times;
        }

        // Parse opening and closing times
        $open_time = strtotime($hours['open_time']);
        $close_time = strtotime($hours['close_time']);
        $current_time = current_time('timestamp');

        // If closed, start from opening time tomorrow
        if (!$is_open || $hours['is_closed']) {
            $next_opening = Multiside_Aroi_Opening_Hours::get_next_opening_time($this->get_site_id());
            if ($next_opening) {
                $start_time = strtotime('tomorrow ' . $next_opening);
            } else {
                $start_time = strtotime('tomorrow ' . $hours['open_time']);
            }
        } else {
            // If open, start from current time + delivery time
            $start_time = $current_time + ($delivery_time * 60);

            // Round to next 15 minutes
            $start_time = ceil($start_time / 900) * 900;

            // Make sure we're within opening hours
            if ($start_time < $open_time) {
                $start_time = $open_time;
            }
        }

        // Generate 15-minute intervals until closing time
        $end_time = $close_time;
        $interval = 900; // 15 minutes in seconds

        for ($time = $start_time; $time <= $end_time; $time += $interval) {
            $times[] = date('H:i', $time);
        }

        // If no times available today, add times for tomorrow
        if (empty($times)) {
            $tomorrow_open = strtotime('tomorrow ' . $hours['open_time']);
            $tomorrow_close = strtotime('tomorrow ' . $hours['close_time']);

            for ($time = $tomorrow_open; $time <= $tomorrow_close; $time += $interval) {
                $times[] = date('H:i', $time) . ' (i morgen)';
            }
        }

        return $times;
    }

    /**
     * Save pickup time to order meta
     *
     * @param int $order_id Order ID
     */
    public function save_pickup_time($order_id) {
        if (isset($_POST['hentes_kl']) && !empty($_POST['hentes_kl'])) {
            $pickup_time = sanitize_text_field($_POST['hentes_kl']);
            update_post_meta($order_id, 'hentes_kl', $pickup_time);
        }
    }

    /**
     * Display pickup time in admin order page
     *
     * @param WC_Order $order Order object
     */
    public function display_pickup_time_admin($order) {
        $pickup_time = $order->get_meta('hentes_kl');

        if ($pickup_time) {
            echo '<p><strong>Hentetid:</strong> ' . esc_html($pickup_time) . '</p>';
        }
    }

    /**
     * Add pickup time to order emails
     *
     * @param array $keys Email meta keys
     * @return array Modified keys
     */
    public function add_pickup_time_to_email($keys) {
        $keys['Hentetid'] = 'hentes_kl';
        return $keys;
    }

    /**
     * Get current site ID
     *
     * @return int Site ID
     */
    private function get_site_id() {
        if (is_multisite()) {
            return get_current_blog_id();
        }

        if (defined('AROI_SITE_ID')) {
            return AROI_SITE_ID;
        }

        return 7; // Default to Namsos
    }
}
