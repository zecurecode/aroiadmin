<?php
/**
 * Checkout management class - handles pickup times and checkout flow
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Checkout_Manager {

    private $api;

    public function __construct() {
        $this->api = new Aroi_Laravel_API();

        // Hook into WooCommerce checkout
        add_action('woocommerce_before_order_notes', array($this, 'add_pickup_time_field'), 10, 1);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_pickup_time_field'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_pickup_time_admin'), 10, 1);
        add_filter('woocommerce_email_order_meta_keys', array($this, 'add_pickup_time_email'));

        // Add notice about opening hours
        add_action('woocommerce_before_order_notes', array($this, 'display_opening_hours_notice'), 5);
    }

    /**
     * Display opening hours notice before checkout
     */
    public function display_opening_hours_notice() {
        $site_id = get_current_blog_id();
        $hours_data = $this->api->get_opening_hours($site_id);
        $delivery_data = $this->api->get_delivery_time($site_id);

        if (!$hours_data || !$delivery_data) {
            return;
        }

        $delivery_time = $delivery_data['delivery_time'] ?? 30;
        $is_open = $hours_data['is_open'] ?? false;
        $status = $hours_data['status'] ?? 0;

        if ($status == 0) {
            echo '<h3 style="color:red; text-align:center;">' .
                 __('The food truck is closed.<br>You can still order for the next day.', 'aroi-laravel-integration') .
                 '</h3>';
        }

        if (!$is_open || $status == 0) {
            wc_print_notice(sprintf(
                __('Orders placed outside our opening hours will be reviewed as soon as the store opens. As soon as the store staff has prepared the food, you will receive a notification by email and SMS.', 'aroi-laravel-integration')
            ), 'success');
        } else {
            echo '<h5>' . sprintf(
                __('It takes approximately %d minutes before your order is ready for pickup', 'aroi-laravel-integration'),
                $delivery_time
            ) . '</h5>';
        }
    }

    /**
     * Add pickup time field to checkout
     */
    public function add_pickup_time_field($checkout) {
        $site_id = get_current_blog_id();
        $pickup_times = $this->api->get_available_pickup_times($site_id);

        if (empty($pickup_times)) {
            $pickup_times = array(
                '12:00' => '12:00'
            );
        }

        woocommerce_form_field('hentes_kl', array(
            'type' => 'select',
            'class' => array('wps-drop'),
            'label' => __('Pickup Time', 'aroi-laravel-integration'),
            'options' => $pickup_times,
            'required' => true,
        ), $checkout->get_value('hentes_kl'));
    }

    /**
     * Save pickup time field to order meta
     */
    public function save_pickup_time_field($order_id) {
        if (!empty($_POST['hentes_kl'])) {
            update_post_meta($order_id, 'hentes_kl', sanitize_text_field($_POST['hentes_kl']));
        }
    }

    /**
     * Display pickup time in admin order details
     */
    public function display_pickup_time_admin($order) {
        $pickup_time = get_post_meta($order->get_id(), 'hentes_kl', true);
        if ($pickup_time) {
            echo '<p><strong>' . __('Pickup Time', 'aroi-laravel-integration') . ':</strong> ' .
                 esc_html($pickup_time) . '</p>';
        }
    }

    /**
     * Add pickup time to email notifications
     */
    public function add_pickup_time_email($keys) {
        $keys[__('Pickup Time', 'aroi-laravel-integration') . ':'] = 'hentes_kl';
        return $keys;
    }

    /**
     * Get formatted opening hours for display
     */
    public function get_formatted_opening_hours($site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }

        $hours_data = $this->api->get_opening_hours($site_id);

        if (!$hours_data) {
            return __('Opening hours not available', 'aroi-laravel-integration');
        }

        $output = sprintf(
            '%s til %s<br>',
            $hours_data['open_time'],
            $hours_data['close_time']
        );

        if ($hours_data['is_open']) {
            $output .= '<span style="color:green;">' .
                       __('Open for pickup today', 'aroi-laravel-integration') .
                       '</span>';
        } else {
            $output .= '<span style="color:red;">' .
                       esc_html($hours_data['message']) .
                       '</span>';
        }

        return $output;
    }

    /**
     * Check if site is currently open (backward compatibility)
     */
    public static function is_open($site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }

        $api = new Aroi_Laravel_API();
        $hours_data = $api->is_open_now($site_id);

        return $hours_data['is_open'] ?? false;
    }
}