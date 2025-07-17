<?php
/**
 * Plugin Name: Aroi Laravel Integration
 * Plugin URI: https://aroiasia.no
 * Description: Integrates WordPress/WooCommerce with Laravel admin system for Aroi Food Truck
 * Version: 1.0.0
 * Author: Aroi Development Team
 * License: GPL v2 or later
 * Text Domain: aroi-laravel
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AROI_LARAVEL_VERSION', '1.0.0');
define('AROI_LARAVEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AROI_LARAVEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AROI_LARAVEL_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
function aroi_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error notice">
                <p><?php _e('Aroi Laravel Integration requires WooCommerce to be installed and active.', 'aroi-laravel'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

// Main plugin class
class Aroi_Laravel_Integration {
    
    private static $instance = null;
    private $api_base_url = '';
    private $api_timeout = 30;
    
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
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load settings
        $this->load_settings();
        
        // Include required files
        $this->includes();
        
        // Hook into WordPress/WooCommerce
        $this->init_hooks();
        
        // Register shortcodes
        $this->register_shortcodes();
    }
    
    /**
     * Load plugin settings
     */
    private function load_settings() {
        $this->api_base_url = get_option('aroi_laravel_api_url', 'https://aroiasia.no/laravel-admin/api/v1');
        $this->api_timeout = get_option('aroi_laravel_api_timeout', 30);
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-location-functions.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-location-listing.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-order-handler.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-opening-hours.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-product-addons.php';
        require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-catering-handler.php';
        
        if (is_admin()) {
            require_once AROI_LARAVEL_PLUGIN_DIR . 'includes/class-admin-settings.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Theme compatibility
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // WooCommerce hooks
        add_action('woocommerce_new_order', array('Aroi_Order_Handler', 'create_order'), 10, 1);
        add_action('woocommerce_payment_complete', array('Aroi_Order_Handler', 'mark_order_paid'), 10, 1);
        add_action('woocommerce_before_order_notes', array('Aroi_Order_Handler', 'display_pickup_time_field'), 10);
        add_action('woocommerce_checkout_update_order_meta', array('Aroi_Order_Handler', 'save_pickup_time'), 10, 1);
        add_action('woocommerce_admin_order_data_after_billing_address', array('Aroi_Order_Handler', 'display_pickup_time_admin'), 10, 1);
        add_filter('woocommerce_email_order_meta_keys', array('Aroi_Order_Handler', 'add_pickup_time_to_email'));
        
        // Product customizations
        add_action('wp', array($this, 'remove_product_zoom'), 100);
        add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'remove_product_image_link'), 10, 2);
        add_filter('woocommerce_product_tabs', array($this, 'remove_product_tabs'), 98);
        
        // Product addons
        add_action('save_post', array('Aroi_Product_Addons', 'update_product_addons'), 30, 3);
        
        // Navigation
        add_filter('woocommerce_after_add_to_cart_button', array($this, 'show_navigation_arrows'), 10, 1);
        add_filter('woocommerce_before_single_product', array($this, 'show_navigation_arrows'), 10, 1);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array('Aroi_Admin_Settings', 'add_menu_page'));
            add_action('admin_init', array('Aroi_Admin_Settings', 'register_settings'));
        }
        
        // AJAX hooks for real-time updates
        add_action('wp_ajax_aroi_check_open_status', array('Aroi_Opening_Hours', 'ajax_check_open_status'));
        add_action('wp_ajax_nopriv_aroi_check_open_status', array('Aroi_Opening_Hours', 'ajax_check_open_status'));
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('gettid', array('Aroi_Location_Functions', 'shortcode_delivery_time'));
        add_shortcode('gettid2', array('Aroi_Location_Functions', 'shortcode_opening_hours'));
        add_shortcode('aroi_location_status', array('Aroi_Location_Functions', 'shortcode_location_status'));
        add_shortcode('aroi_weekly_hours', array('Aroi_Location_Functions', 'shortcode_weekly_hours'));
        add_shortcode('aroi_locations', array('Aroi_Location_Listing', 'shortcode_location_cards'));
        add_shortcode('aroi_single_location', array('Aroi_Location_Listing', 'shortcode_single_location'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'aroi-laravel-style',
            AROI_LARAVEL_PLUGIN_URL . 'assets/css/aroi-frontend.css',
            array(),
            AROI_LARAVEL_VERSION
        );
        
        wp_enqueue_script(
            'aroi-laravel-script',
            AROI_LARAVEL_PLUGIN_URL . 'assets/js/aroi-frontend.js',
            array('jquery'),
            AROI_LARAVEL_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('aroi-laravel-script', 'aroi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aroi_ajax_nonce'),
            'site_id' => get_current_blog_id()
        ));
    }
    
    /**
     * Remove product zoom
     */
    public function remove_product_zoom() {
        remove_theme_support('wc-product-gallery-zoom');
    }
    
    /**
     * Remove product image link
     */
    public function remove_product_image_link($html, $post_id) {
        return preg_replace("!<(a|/a).*?>!", '', $html);
    }
    
    /**
     * Remove product tabs
     */
    public function remove_product_tabs($tabs) {
        unset($tabs['additional_information']);
        return $tabs;
    }
    
    /**
     * Show navigation arrows
     */
    public function show_navigation_arrows() {
        $terms = wp_get_post_terms(get_the_id(), 'product_cat', array('include_children' => false));
        $term = reset($terms);
        if ($term) {
            $term_link = get_term_link($term->term_id, 'product_cat');
            echo '<h2 class="dsklink"><a href="' . esc_url($term_link) . '"><i class="fas fa-angle-left" style="font-size:48px;color:#8a3794"></i></a></h2>';
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option('aroi_laravel_api_url', 'https://aroiasia.no/laravel-admin/api/v1');
        add_option('aroi_laravel_api_timeout', 30);
        add_option('aroi_laravel_cache_duration', 300); // 5 minutes
        
        // Create database table for caching if needed
        $this->create_cache_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('aroi_hourly_sync');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create cache table
     */
    private function create_cache_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aroi_cache';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            expiry datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expiry (expiry)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get API client instance
     */
    public function get_api_client() {
        return new Aroi_API_Client($this->api_base_url, $this->api_timeout);
    }
    
    /**
     * Get current site ID
     */
    public static function get_site_id() {
        return get_current_blog_id();
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    if (aroi_check_woocommerce()) {
        Aroi_Laravel_Integration::get_instance();
    }
});

// Make functions globally available for backward compatibility
if (!function_exists('getCaller')) {
    function getCaller() {
        return Aroi_Laravel_Integration::get_site_id();
    }
}

if (!function_exists('gettid_function')) {
    function gettid_function($id) {
        return Aroi_Location_Functions::get_delivery_time($id);
    }
}

if (!function_exists('gettid_function2')) {
    function gettid_function2($atts) {
        return Aroi_Location_Functions::shortcode_opening_hours($atts);
    }
}