<?php
/**
 * Plugin Name: MultiSide Aroi Integration
 * Plugin URI: https://infodesk.no
 * Description: Modern WordPress plugin for Aroi Food Truck multi-location management. Integrates WooCommerce orders with Laravel API, PCKasse POS, and SMS notifications.
 * Version: 2.0.0
 * Author: InfoDesk AS
 * Author URI: https://infodesk.no
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multiside-aroi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MULTISIDE_AROI_VERSION', '2.0.0');
define('MULTISIDE_AROI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MULTISIDE_AROI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MULTISIDE_AROI_PLUGIN_FILE', __FILE__);

// Database configuration
define('MULTISIDE_AROI_DB_HOST', 'localhost:3306');
define('MULTISIDE_AROI_DB_NAME', 'admin_aroi');
define('MULTISIDE_AROI_DB_USER', 'adminaroi');
define('MULTISIDE_AROI_DB_PASS', 'b^754Xws');

/**
 * Main Plugin Class
 */
class Multiside_Aroi_Integration {

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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-database.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-sms-service.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-pckasse-service.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-opening-hours.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-order-handler.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-department-cards.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-checkout-manager.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Admin enqueue
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Check for WooCommerce
        add_action('admin_notices', array($this, 'check_woocommerce'));

        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // Initialize order handler
        Multiside_Aroi_Order_Handler::get_instance();

        // Initialize checkout manager
        Multiside_Aroi_Checkout_Manager::get_instance();
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'multiside-aroi-cards',
            MULTISIDE_AROI_PLUGIN_URL . 'assets/css/department-cards.css',
            array(),
            MULTISIDE_AROI_VERSION
        );

        wp_enqueue_style(
            'multiside-aroi-checkout',
            MULTISIDE_AROI_PLUGIN_URL . 'assets/css/checkout.css',
            array(),
            MULTISIDE_AROI_VERSION
        );

        wp_enqueue_script(
            'multiside-aroi-frontend',
            MULTISIDE_AROI_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MULTISIDE_AROI_VERSION,
            true
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style(
            'multiside-aroi-admin',
            MULTISIDE_AROI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MULTISIDE_AROI_VERSION
        );
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="error"><p><strong>MultiSide Aroi Integration</strong> requires WooCommerce to be installed and active.</p></div>';
        }
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('aroi_department_cards', array('Multiside_Aroi_Department_Cards', 'render_shortcode'));
        add_shortcode('aroi_opening_hours', array('Multiside_Aroi_Opening_Hours', 'render_shortcode'));
        add_shortcode('aroi_delivery_time', array('Multiside_Aroi_Opening_Hours', 'render_delivery_time'));
    }
}

/**
 * Initialize the plugin
 */
function multiside_aroi_init() {
    return Multiside_Aroi_Integration::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'multiside_aroi_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'multiside_aroi_activate');
function multiside_aroi_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires PHP 7.4 or higher.');
    }

    // Check WooCommerce
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'multiside_aroi_deactivate');
function multiside_aroi_deactivate() {
    flush_rewrite_rules();
}
