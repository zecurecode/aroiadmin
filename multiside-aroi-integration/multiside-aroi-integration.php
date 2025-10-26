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
        // Core classes (order matters - database first, then config)
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-database.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-site-config.php';  // DYNAMIC configuration from database

        // Service classes (depends on site-config)
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-sms-service.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-pckasse-service.php';
        require_once MULTISIDE_AROI_PLUGIN_DIR . 'includes/class-opening-hours.php';

        // Handler classes
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

        // Check dynamic configuration
        add_action('admin_notices', array($this, 'check_configuration'));

        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // Admin menu for configuration validator
        add_action('admin_menu', array($this, 'add_admin_menu'));

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
     * Check dynamic configuration
     */
    public function check_configuration() {
        // Only show on relevant admin pages
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('dashboard', 'plugins', 'toplevel_page_multiside-aroi-config'))) {
            return;
        }

        $validation = Multiside_Aroi_Site_Config::validate_config();

        // Show errors
        if (!empty($validation['errors'])) {
            echo '<div class="error"><p><strong>MultiSide Aroi Integration - Configuration Errors:</strong></p><ul>';
            foreach ($validation['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }

        // Show warnings
        if (!empty($validation['warnings'])) {
            echo '<div class="notice notice-warning"><p><strong>MultiSide Aroi Integration - Warnings:</strong></p><ul>';
            foreach ($validation['warnings'] as $warning) {
                echo '<li>' . esc_html($warning) . '</li>';
            }
            echo '</ul></div>';
        }

        // Show success if valid
        if ($validation['valid'] && empty($validation['warnings'])) {
            $config = $validation['config'];
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>MultiSide Aroi Integration:</strong> ';
            echo sprintf(
                'Konfigurert for %s (Site ID: %d, License: %s)',
                esc_html($config['location_name']),
                $config['site_id'],
                $config['pckasse_license'] ? $config['pckasse_license'] : 'N/A'
            );
            echo '</p></div>';
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Aroi Configuration',
            'Aroi Config',
            'manage_options',
            'multiside-aroi-config',
            array($this, 'render_config_page'),
            'dashicons-admin-settings',
            80
        );
    }

    /**
     * Render configuration page
     */
    public function render_config_page() {
        $validation = Multiside_Aroi_Site_Config::validate_config();
        $config = $validation['config'];

        ?>
        <div class="wrap">
            <h1>MultiSide Aroi Integration - Configuration</h1>

            <div class="card" style="max-width: 800px;">
                <h2>Dynamic Configuration Status</h2>

                <?php if ($validation['valid']): ?>
                    <div class="notice notice-success inline"><p><strong>✅ Configuration is valid!</strong></p></div>
                <?php else: ?>
                    <div class="notice notice-error inline"><p><strong>❌ Configuration has errors</strong></p></div>
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th>Site ID</th>
                        <td><strong><?php echo esc_html($config['site_id'] ? $config['site_id'] : 'NOT DETECTED'); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Location Name</th>
                        <td><?php echo esc_html($config['location_name']); ?></td>
                    </tr>
                    <tr>
                        <th>PCKasse License</th>
                        <td><strong><?php echo esc_html($config['pckasse_license'] ? $config['pckasse_license'] : 'NOT CONFIGURED'); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Delivery Time</th>
                        <td><?php echo esc_html($config['delivery_time']); ?> minutes</td>
                    </tr>
                    <tr>
                        <th>SMS Sender</th>
                        <td><?php echo esc_html($config['sms_sender']); ?></td>
                    </tr>
                    <tr>
                        <th>SMS Username</th>
                        <td><?php echo esc_html($config['sms_credentials']['username'] ? '✓ Configured' : '❌ Missing'); ?></td>
                    </tr>
                    <tr>
                        <th>SMS API URL</th>
                        <td><?php echo esc_html($config['sms_credentials']['url']); ?></td>
                    </tr>
                </table>

                <?php if (!empty($validation['errors'])): ?>
                    <h3>Errors</h3>
                    <ul style="color: red;">
                        <?php foreach ($validation['errors'] as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($validation['warnings'])): ?>
                    <h3>Warnings</h3>
                    <ul style="color: orange;">
                        <?php foreach ($validation['warnings'] as $warning): ?>
                            <li><?php echo esc_html($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h3>Detection Method</h3>
                <p>
                    <?php if (is_multisite()): ?>
                        <strong>WordPress Multisite:</strong> Site ID detected automatically from Blog ID (<?php echo get_current_blog_id(); ?>)
                    <?php else: ?>
                        <strong>Single Site:</strong> Site ID detected from database or URL matching
                    <?php endif; ?>
                </p>

                <h3>All Sites in Database</h3>
                <?php
                $all_sites = Multiside_Aroi_Site_Config::get_all_site_ids();
                if (!empty($all_sites)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Site ID</th>
                                <th>Location Name</th>
                                <th>PCKasse License</th>
                                <th>Delivery Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_sites as $sid): ?>
                                <tr>
                                    <td><?php echo esc_html($sid); ?></td>
                                    <td><?php echo esc_html(Multiside_Aroi_Site_Config::get_location_name($sid)); ?></td>
                                    <td><?php echo esc_html(Multiside_Aroi_Site_Config::get_pckasse_license($sid) ?: 'N/A'); ?></td>
                                    <td><?php echo esc_html(Multiside_Aroi_Site_Config::get_delivery_time($sid)); ?> min</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <p style="margin-top: 20px;">
                    <em>All configuration is loaded dynamically from the admin_aroi database. No hardcoded values!</em>
                </p>
            </div>
        </div>
        <?php
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
