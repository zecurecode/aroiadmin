<?php
/**
 * Plugin Name: Aroi Laravel Integration
 * Plugin URI: https://aroiasia.no
 * Description: WordPress multisite plugin that integrates WooCommerce with the Aroi Laravel admin system. Replaces direct database connections with Laravel API calls.
 * Version: 1.0.0
 * Author: Aroi Asia
 * Author URI: https://aroiasia.no
 * Network: true
 * Text Domain: aroi-laravel-integration
 * Domain Path: /languages
 *
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AROI_LARAVEL_PLUGIN_FILE', __FILE__);
define('AROI_LARAVEL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AROI_LARAVEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AROI_LARAVEL_PLUGIN_VERSION', '1.0.0');

// Define Laravel API base URL
define('AROI_LARAVEL_API_BASE', 'https://aroiasia.no/laravel-admin/api/v1');

/**
 * Main plugin class
 */
class AroiLaravelIntegration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load plugin files
        $this->load_dependencies();

        // Initialize components
        new Aroi_Laravel_API();
        new Aroi_Order_Handler();
        new Aroi_Checkout_Manager();
        new Aroi_Admin_Interface();

        // Add shortcodes
        add_shortcode('aroi_opening_hours', array($this, 'opening_hours_shortcode'));
        add_shortcode('aroi_delivery_time', array($this, 'delivery_time_shortcode'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once AROI_LARAVEL_PLUGIN_PATH . 'includes/class-aroi-laravel-api.php';
        require_once AROI_LARAVEL_PLUGIN_PATH . 'includes/class-aroi-order-handler.php';
        require_once AROI_LARAVEL_PLUGIN_PATH . 'includes/class-aroi-checkout-manager.php';
        require_once AROI_LARAVEL_PLUGIN_PATH . 'includes/class-aroi-admin-interface.php';
        require_once AROI_LARAVEL_PLUGIN_PATH . 'includes/class-aroi-site-manager.php';
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'aroi-laravel-integration',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('aroi_laravel_api_base')) {
            update_option('aroi_laravel_api_base', AROI_LARAVEL_API_BASE);
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Aroi Laravel Integration requires WooCommerce to be installed and active.', 'aroi-laravel-integration');
        echo '</p></div>';
    }

    /**
     * Opening hours shortcode
     */
    public function opening_hours_shortcode($atts) {
        $atts = shortcode_atts(array(
            'site' => get_current_blog_id(),
            'format' => 'simple'
        ), $atts, 'aroi_opening_hours');

        $api = new Aroi_Laravel_API();
        $hours = $api->get_opening_hours($atts['site']);

        if (!$hours) {
            return __('Opening hours not available', 'aroi-laravel-integration');
        }

        if ($atts['format'] === 'detailed') {
            return $this->format_detailed_hours($hours);
        }

        return $this->format_simple_hours($hours);
    }

    /**
     * Delivery time shortcode
     */
    public function delivery_time_shortcode($atts) {
        $atts = shortcode_atts(array(
            'site' => get_current_blog_id()
        ), $atts, 'aroi_delivery_time');

        $api = new Aroi_Laravel_API();
        $delivery_data = $api->get_delivery_time($atts['site']);

        if (!$delivery_data) {
            return __('Delivery time not available', 'aroi-laravel-integration');
        }

        return sprintf(
            __('Estimated preparation time: %d minutes', 'aroi-laravel-integration'),
            $delivery_data['delivery_time']
        );
    }

    /**
     * Format simple opening hours
     */
    private function format_simple_hours($hours) {
        if (!$hours['is_open']) {
            return '<span style="color:red;">' . $hours['message'] . '</span>';
        }

        return sprintf(
            '%s til %s<br><span style="color:green;">%s</span>',
            $hours['open_time'],
            $hours['close_time'],
            __('Open for pickup today', 'aroi-laravel-integration')
        );
    }

    /**
     * Format detailed opening hours
     */
    private function format_detailed_hours($hours) {
        $output = sprintf(
            '%s til %s<br>',
            $hours['open_time'],
            $hours['close_time']
        );

        if ($hours['is_open']) {
            $output .= '<span style="color:green;">' . __('Open for pickup today', 'aroi-laravel-integration') . '</span>';
        } else {
            $output .= '<span style="color:red;">' . $hours['message'] . '</span>';
        }

        return $output;
    }
}

// Initialize the plugin
function aroi_laravel_integration() {
    return AroiLaravelIntegration::get_instance();
}

// Start the plugin
aroi_laravel_integration();