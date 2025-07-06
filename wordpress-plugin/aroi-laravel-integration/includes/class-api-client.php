<?php
/**
 * API Client for Laravel Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_API_Client {
    
    private $base_url;
    private $timeout;
    private $cache_duration;
    
    /**
     * Constructor
     */
    public function __construct($base_url, $timeout = 30) {
        $this->base_url = rtrim($base_url, '/');
        $this->timeout = $timeout;
        $this->cache_duration = get_option('aroi_laravel_cache_duration', 300);
    }
    
    /**
     * Make API request
     */
    public function request($endpoint, $method = 'GET', $data = null, $use_cache = true) {
        $url = $this->base_url . $endpoint;
        $cache_key = 'aroi_api_' . md5($url . serialize($data));
        
        // Try to get from cache for GET requests
        if ($method === 'GET' && $use_cache) {
            $cached = $this->get_cache($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Prepare request arguments
        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
        );
        
        // Add body for POST/PUT/PATCH requests
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            $this->log_error('API request failed', array(
                'url' => $url,
                'error' => $response->get_error_message()
            ));
            return null;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Check for HTTP errors
        if ($response_code < 200 || $response_code >= 300) {
            $this->log_error('API returned error', array(
                'url' => $url,
                'code' => $response_code,
                'body' => $response_body
            ));
            return null;
        }
        
        // Parse JSON response
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Invalid JSON response', array(
                'url' => $url,
                'body' => $response_body
            ));
            return null;
        }
        
        // Cache successful GET requests
        if ($method === 'GET' && $use_cache) {
            $this->set_cache($cache_key, $data);
        }
        
        return $data;
    }
    
    /**
     * GET request
     */
    public function get($endpoint, $use_cache = true) {
        return $this->request($endpoint, 'GET', null, $use_cache);
    }
    
    /**
     * POST request
     */
    public function post($endpoint, $data) {
        return $this->request($endpoint, 'POST', $data, false);
    }
    
    /**
     * PUT request
     */
    public function put($endpoint, $data) {
        return $this->request($endpoint, 'PUT', $data, false);
    }
    
    /**
     * DELETE request
     */
    public function delete($endpoint) {
        return $this->request($endpoint, 'DELETE', null, false);
    }
    
    /**
     * Get from cache
     */
    private function get_cache($key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aroi_cache';
        $now = current_time('mysql');
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT cache_value FROM $table_name WHERE cache_key = %s AND expiry > %s",
            $key,
            $now
        ));
        
        if ($result) {
            return json_decode($result, true);
        }
        
        return false;
    }
    
    /**
     * Set cache
     */
    private function set_cache($key, $value) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aroi_cache';
        $expiry = date('Y-m-d H:i:s', time() + $this->cache_duration);
        
        $wpdb->replace(
            $table_name,
            array(
                'cache_key' => $key,
                'cache_value' => json_encode($value),
                'expiry' => $expiry
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aroi_cache';
        $wpdb->query("DELETE FROM $table_name WHERE 1=1");
    }
    
    /**
     * Clear expired cache
     */
    public function clear_expired_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aroi_cache';
        $now = current_time('mysql');
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE expiry < %s",
            $now
        ));
    }
    
    /**
     * Log error
     */
    private function log_error($message, $context = array()) {
        if (WP_DEBUG_LOG) {
            error_log(sprintf(
                '[Aroi Laravel Integration] %s: %s',
                $message,
                json_encode($context)
            ));
        }
    }
}