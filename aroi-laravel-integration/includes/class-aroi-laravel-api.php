<?php
/**
 * Laravel API communication class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Laravel_API {

    private $api_base;
    private $timeout = 30;

    public function __construct() {
        $this->api_base = get_option('aroi_laravel_api_base', AROI_LARAVEL_API_BASE);
    }

    /**
     * Make API request to Laravel
     */
    private function make_request($endpoint, $method = 'GET', $data = null) {
        $url = rtrim($this->api_base, '/') . '/' . ltrim($endpoint, '/');

        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
        );

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('Aroi Laravel API Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 200 && $status_code < 300) {
            return json_decode($body, true);
        }

        error_log("Aroi Laravel API Error: HTTP {$status_code} - {$body}");
        return false;
    }

    /**
     * Get location information
     */
    public function get_location($site_id) {
        return $this->make_request("wordpress/location/{$site_id}");
    }

    /**
     * Get delivery time for location
     */
    public function get_delivery_time($site_id) {
        return $this->make_request("wordpress/location/{$site_id}/delivery-time");
    }

    /**
     * Get opening hours for location
     */
    public function get_opening_hours($site_id) {
        return $this->make_request("wordpress/location/{$site_id}/opening-hours");
    }

    /**
     * Get all opening hours for location (weekly schedule)
     */
    public function get_all_opening_hours($site_id) {
        return $this->make_request("wordpress/location/{$site_id}/all-hours");
    }

    /**
     * Check if location is open now
     */
    public function is_open_now($site_id) {
        return $this->make_request("wordpress/location/{$site_id}/is-open");
    }

    /**
     * Update location status
     */
    public function update_status($site_id, $status) {
        return $this->make_request(
            "wordpress/location/{$site_id}/update-status",
            'POST',
            array('status' => $status)
        );
    }

    /**
     * Create order in Laravel system
     */
    public function create_order($order_data) {
        return $this->make_request('orders', 'POST', $order_data);
    }

    /**
     * Mark order as paid
     */
    public function mark_order_paid($order_id, $site_id) {
        return $this->make_request('orders/mark-paid', 'POST', array(
            'ordreid' => $order_id,
            'site' => $site_id
        ));
    }

    /**
     * Get available pickup times based on opening hours and delivery time
     */
    public function get_available_pickup_times($site_id) {
        $hours_data = $this->get_opening_hours($site_id);
        $delivery_data = $this->get_delivery_time($site_id);

        if (!$hours_data || !$delivery_data) {
            return array();
        }

        return $this->calculate_pickup_times($hours_data, $delivery_data);
    }

    /**
     * Calculate available pickup times
     */
    private function calculate_pickup_times($hours_data, $delivery_data) {
        date_default_timezone_set('Europe/Oslo');

        $options = array();
        $delivery_time = $delivery_data['delivery_time'] ?? 30;
        $current_time = time();

        // Parse opening hours
        $open_time = strtotime($hours_data['open_time']);
        $close_time = strtotime($hours_data['close_time']);
        $is_open = $hours_data['is_open'] ?? false;
        $status = $hours_data['status'] ?? 0;

        // If closed, start from opening time
        if (!$is_open || $current_time < $open_time || $current_time > $close_time || $status == 0) {
            $earliest_time = $open_time;
            $options[date('H:i', $open_time)] = date('H:i', $open_time);
        } else {
            // If open, start from current time + delivery time
            $earliest_time = strtotime("+{$delivery_time} minutes", $current_time);
            $options[date('H:i', $earliest_time)] = date('H:i', $earliest_time);
        }

        // Round to next 15 minutes
        $earliest_time = ceil($earliest_time / (15 * 60)) * (15 * 60);

        // Generate 15-minute intervals until closing time
        while ($earliest_time <= $close_time && $earliest_time >= $open_time) {
            $time_string = date('H:i', $earliest_time);
            $options[$time_string] = $time_string;
            $earliest_time = strtotime('+15 minutes', $earliest_time);
        }

        return $options;
    }

    /**
     * Get site-specific license (for backward compatibility)
     */
    public function get_site_license($site_id) {
        $location_data = $this->get_location($site_id);
        return $location_data['license'] ?? null;
    }

    /**
     * Health check for Laravel API
     */
    public function health_check() {
        $response = $this->make_request('health');
        return $response !== false;
    }
}