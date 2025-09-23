<?php
/**
 * Admin interface for plugin settings and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Admin_Interface {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_aroi_test_connection', array($this, 'test_api_connection'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Aroi Laravel Integration', 'aroi-laravel-integration'),
            __('Aroi Laravel', 'aroi-laravel-integration'),
            'manage_options',
            'aroi-laravel-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'aroi_laravel_settings',
            'aroi_laravel_api_base',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => AROI_LARAVEL_API_BASE
            )
        );

        add_settings_section(
            'aroi_laravel_api_section',
            __('API Configuration', 'aroi-laravel-integration'),
            array($this, 'api_section_callback'),
            'aroi-laravel-settings'
        );

        add_settings_field(
            'aroi_laravel_api_base',
            __('Laravel API Base URL', 'aroi-laravel-integration'),
            array($this, 'api_base_field_callback'),
            'aroi-laravel-settings',
            'aroi_laravel_api_section'
        );
    }

    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Aroi Laravel Integration Settings', 'aroi-laravel-integration'); ?></h1>

            <?php $this->display_site_info(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('aroi_laravel_settings');
                do_settings_sections('aroi-laravel-settings');
                submit_button();
                ?>
            </form>

            <div class="aroi-test-section">
                <h2><?php echo esc_html__('API Connection Test', 'aroi-laravel-integration'); ?></h2>
                <button type="button" id="test-api-connection" class="button">
                    <?php echo esc_html__('Test Connection', 'aroi-laravel-integration'); ?>
                </button>
                <div id="test-results"></div>
            </div>

            <?php $this->display_debug_info(); ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-api-connection').click(function() {
                var $button = $(this);
                var $results = $('#test-results');

                $button.prop('disabled', true).text('<?php echo esc_js__('Testing...', 'aroi-laravel-integration'); ?>');
                $results.html('<p><?php echo esc_js__('Testing connection...', 'aroi-laravel-integration'); ?></p>');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'aroi_test_connection',
                        nonce: '<?php echo wp_create_nonce('aroi_test_connection'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $results.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $results.html('<div class="notice notice-error"><p><?php echo esc_js__('Connection test failed', 'aroi-laravel-integration'); ?></p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php echo esc_js__('Test Connection', 'aroi-laravel-integration'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display site information
     */
    private function display_site_info() {
        $site_id = get_current_blog_id();
        $site_manager = new Aroi_Site_Manager();
        $site_info = $site_manager->get_site_info($site_id);

        ?>
        <div class="notice notice-info">
            <h3><?php echo esc_html__('Current Site Information', 'aroi-laravel-integration'); ?></h3>
            <p><strong><?php echo esc_html__('Site ID:', 'aroi-laravel-integration'); ?></strong> <?php echo esc_html($site_id); ?></p>
            <p><strong><?php echo esc_html__('Site Name:', 'aroi-laravel-integration'); ?></strong> <?php echo esc_html($site_info['name']); ?></p>
            <p><strong><?php echo esc_html__('License:', 'aroi-laravel-integration'); ?></strong> <?php echo esc_html($site_info['license']); ?></p>
        </div>
        <?php
    }

    /**
     * Display debug information
     */
    private function display_debug_info() {
        $api = new Aroi_Laravel_API();
        $site_id = get_current_blog_id();

        ?>
        <div class="aroi-debug-section">
            <h2><?php echo esc_html__('Debug Information', 'aroi-laravel-integration'); ?></h2>
            <div class="notice notice-warning">
                <p><strong><?php echo esc_html__('Current Site ID:', 'aroi-laravel-integration'); ?></strong> <?php echo esc_html($site_id); ?></p>
                <p><strong><?php echo esc_html__('API Base URL:', 'aroi-laravel-integration'); ?></strong> <?php echo esc_html(get_option('aroi_laravel_api_base')); ?></p>
                <p><strong><?php echo esc_html__('WooCommerce Active:', 'aroi-laravel-integration'); ?></strong> <?php echo class_exists('WooCommerce') ? 'Yes' : 'No'; ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . esc_html__('Configure the Laravel API connection settings.', 'aroi-laravel-integration') . '</p>';
    }

    /**
     * API base field callback
     */
    public function api_base_field_callback() {
        $value = get_option('aroi_laravel_api_base', AROI_LARAVEL_API_BASE);
        echo '<input type="url" id="aroi_laravel_api_base" name="aroi_laravel_api_base" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('The base URL for the Laravel API endpoints.', 'aroi-laravel-integration') . '</p>';
    }

    /**
     * Test API connection via AJAX
     */
    public function test_api_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'aroi_test_connection')) {
            wp_die(__('Security check failed', 'aroi-laravel-integration'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'aroi-laravel-integration'));
        }

        $api = new Aroi_Laravel_API();
        $site_id = get_current_blog_id();

        // Test various endpoints
        $tests = array(
            'location' => $api->get_location($site_id),
            'opening_hours' => $api->get_opening_hours($site_id),
            'delivery_time' => $api->get_delivery_time($site_id),
        );

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test => $result) {
            if ($result !== false) {
                $passed++;
            }
        }

        if ($passed === $total) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('API connection successful! All %d tests passed.', 'aroi-laravel-integration'),
                    $total
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('API connection issues detected. %d of %d tests passed.', 'aroi-laravel-integration'),
                    $passed,
                    $total
                )
            ));
        }
    }
}