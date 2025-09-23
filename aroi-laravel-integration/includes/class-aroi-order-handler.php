<?php
/**
 * Order handling class - replaces direct database operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Order_Handler {

    private $api;

    public function __construct() {
        $this->api = new Aroi_Laravel_API();

        // Hook into WooCommerce order events
        add_action('woocommerce_new_order', array($this, 'handle_new_order'), 1, 1);
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'), 1, 1);
    }

    /**
     * Handle new WooCommerce order - send to Laravel
     */
    public function handle_new_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $site_id = get_current_blog_id();

        // Prepare order data for Laravel
        $order_data = array(
            'fornavn' => $order->get_billing_first_name(),
            'etternavn' => $order->get_billing_last_name(),
            'telefon' => $order->get_billing_phone(),
            'ordreid' => $order_id,
            'epost' => $order->get_billing_email(),
            'site' => $site_id,
        );

        // Send to Laravel
        $response = $this->api->create_order($order_data);

        if ($response && $response['success']) {
            // Add order note
            $order->add_order_note(
                __('Order sent to Aroi Laravel system', 'aroi-laravel-integration')
            );
        } else {
            // Log error
            error_log("Failed to send order {$order_id} to Laravel system");
            $order->add_order_note(
                __('Failed to send order to Aroi system', 'aroi-laravel-integration')
            );
        }
    }

    /**
     * Handle payment completion - mark as paid in Laravel
     */
    public function handle_payment_complete($order_id) {
        $site_id = get_current_blog_id();

        // Mark order as paid in Laravel
        $response = $this->api->mark_order_paid($order_id, $site_id);

        if ($response && $response['success']) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(
                    __('Payment confirmed in Aroi Laravel system', 'aroi-laravel-integration')
                );
            }
        } else {
            error_log("Failed to mark order {$order_id} as paid in Laravel system");
        }
    }

    /**
     * Get site caller ID (backward compatibility)
     */
    public static function get_caller() {
        return get_current_blog_id();
    }

    /**
     * Get site license (backward compatibility)
     */
    public static function get_site_license($site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }

        $api = new Aroi_Laravel_API();
        return $api->get_site_license($site_id);
    }
}