<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Admin_Settings {
    
    /**
     * Add menu page
     */
    public static function add_menu_page() {
        add_menu_page(
            __('Aroi Laravel Integration', 'aroi-laravel'),
            __('Aroi Integration', 'aroi-laravel'),
            'manage_options',
            'aroi-laravel-settings',
            array(__CLASS__, 'render_settings_page'),
            'dashicons-rest-api',
            30
        );
        
        add_submenu_page(
            'aroi-laravel-settings',
            __('Settings', 'aroi-laravel'),
            __('Settings', 'aroi-laravel'),
            'manage_options',
            'aroi-laravel-settings',
            array(__CLASS__, 'render_settings_page')
        );
        
        add_submenu_page(
            'aroi-laravel-settings',
            __('Status', 'aroi-laravel'),
            __('Status', 'aroi-laravel'),
            'manage_options',
            'aroi-laravel-status',
            array(__CLASS__, 'render_status_page')
        );
        
        add_submenu_page(
            'aroi-laravel-settings',
            __('Tools', 'aroi-laravel'),
            __('Tools', 'aroi-laravel'),
            'manage_options',
            'aroi-laravel-tools',
            array(__CLASS__, 'render_tools_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        // API Settings
        register_setting('aroi_laravel_settings', 'aroi_laravel_api_url');
        register_setting('aroi_laravel_settings', 'aroi_laravel_api_timeout');
        register_setting('aroi_laravel_settings', 'aroi_laravel_cache_duration');
        
        // Add settings sections
        add_settings_section(
            'aroi_laravel_api_section',
            __('API Configuration', 'aroi-laravel'),
            array(__CLASS__, 'render_api_section'),
            'aroi_laravel_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'aroi_laravel_api_url',
            __('API Base URL', 'aroi-laravel'),
            array(__CLASS__, 'render_api_url_field'),
            'aroi_laravel_settings',
            'aroi_laravel_api_section'
        );
        
        add_settings_field(
            'aroi_laravel_api_timeout',
            __('API Timeout (seconds)', 'aroi-laravel'),
            array(__CLASS__, 'render_api_timeout_field'),
            'aroi_laravel_settings',
            'aroi_laravel_api_section'
        );
        
        add_settings_field(
            'aroi_laravel_cache_duration',
            __('Cache Duration (seconds)', 'aroi-laravel'),
            array(__CLASS__, 'render_cache_duration_field'),
            'aroi_laravel_settings',
            'aroi_laravel_api_section'
        );
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('aroi_laravel_messages'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('aroi_laravel_settings');
                do_settings_sections('aroi_laravel_settings');
                submit_button(__('Save Settings', 'aroi-laravel'));
                ?>
            </form>
            
            <div class="aroi-info-box">
                <h3><?php _e('Current Configuration', 'aroi-laravel'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Site ID:', 'aroi-laravel'); ?></strong></td>
                        <td><?php echo Aroi_Laravel_Integration::get_site_id(); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Location Name:', 'aroi-laravel'); ?></strong></td>
                        <td><?php echo Aroi_Location_Functions::get_site_name(Aroi_Laravel_Integration::get_site_id()); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Plugin Version:', 'aroi-laravel'); ?></strong></td>
                        <td><?php echo AROI_LARAVEL_VERSION; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render status page
     */
    public static function render_status_page() {
        $site_id = Aroi_Laravel_Integration::get_site_id();
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        
        // Test API connection
        $test_response = $api->get("/wordpress/location/{$site_id}");
        $api_status = $test_response ? 'connected' : 'error';
        
        // Get current status
        $status_response = $api->get("/wordpress/location/{$site_id}/is-open", false);
        
        ?>
        <div class="wrap">
            <h1><?php _e('System Status', 'aroi-laravel'); ?></h1>
            
            <div class="aroi-status-grid">
                <div class="status-card">
                    <h3><?php _e('API Connection', 'aroi-laravel'); ?></h3>
                    <div class="status-indicator <?php echo $api_status; ?>">
                        <?php if ($api_status === 'connected'): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Connected', 'aroi-laravel'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Connection Error', 'aroi-laravel'); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($status_response): ?>
                <div class="status-card">
                    <h3><?php _e('Location Status', 'aroi-laravel'); ?></h3>
                    <div class="status-indicator <?php echo $status_response['is_open'] ? 'open' : 'closed'; ?>">
                        <?php echo esc_html($status_response['message']); ?>
                    </div>
                    <?php if ($status_response['open_time'] && $status_response['close_time']): ?>
                        <p><?php echo esc_html($status_response['open_time']); ?> - <?php echo esc_html($status_response['close_time']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="status-card">
                    <h3><?php _e('Cache Status', 'aroi-laravel'); ?></h3>
                    <button id="clear-cache" class="button button-secondary">
                        <?php _e('Clear Cache', 'aroi-laravel'); ?>
                    </button>
                    <div id="cache-message"></div>
                </div>
            </div>
            
            <h2><?php _e('Recent Orders', 'aroi-laravel'); ?></h2>
            <?php self::render_recent_orders_table(); ?>
        </div>
        
        <style>
            .aroi-status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .status-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                border-radius: 4px;
            }
            .status-indicator {
                font-size: 18px;
                font-weight: bold;
                margin: 10px 0;
            }
            .status-indicator.connected,
            .status-indicator.open {
                color: #46b450;
            }
            .status-indicator.error,
            .status-indicator.closed {
                color: #dc3232;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                $('#clear-cache').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'aroi_clear_cache',
                        nonce: '<?php echo wp_create_nonce('aroi_clear_cache'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#cache-message').html('<p style="color: green;">' + response.data.message + '</p>');
                        } else {
                            $('#cache-message').html('<p style="color: red;">Error clearing cache</p>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Render tools page
     */
    public static function render_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Tools', 'aroi-laravel'); ?></h1>
            
            <div class="tool-box">
                <h3><?php _e('Product Addons Migration', 'aroi-laravel'); ?></h3>
                <p><?php _e('Migrate DSK product addons to WooCommerce Product Addons format.', 'aroi-laravel'); ?></p>
                <button id="migrate-addons" class="button button-primary">
                    <?php _e('Run Migration', 'aroi-laravel'); ?>
                </button>
                <div id="migration-result"></div>
            </div>
            
            <div class="tool-box">
                <h3><?php _e('Test API Endpoints', 'aroi-laravel'); ?></h3>
                <p><?php _e('Test various API endpoints to ensure proper connectivity.', 'aroi-laravel'); ?></p>
                <button id="test-api" class="button button-secondary">
                    <?php _e('Run Tests', 'aroi-laravel'); ?>
                </button>
                <div id="test-results"></div>
            </div>
        </div>
        
        <style>
            .tool-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                $('#migrate-addons').on('click', function() {
                    var $button = $(this);
                    $button.prop('disabled', true);
                    
                    $.post(ajaxurl, {
                        action: 'aroi_migrate_addons',
                        nonce: '<?php echo wp_create_nonce('aroi_migrate_addons'); ?>'
                    }, function(response) {
                        $button.prop('disabled', false);
                        if (response.success) {
                            $('#migration-result').html('<p style="color: green;">' + response.data.message + '</p>');
                        } else {
                            $('#migration-result').html('<p style="color: red;">Migration failed</p>');
                        }
                    });
                });
                
                $('#test-api').on('click', function() {
                    var $button = $(this);
                    $button.prop('disabled', true);
                    $('#test-results').html('<p>Running tests...</p>');
                    
                    $.post(ajaxurl, {
                        action: 'aroi_test_api',
                        nonce: '<?php echo wp_create_nonce('aroi_test_api'); ?>'
                    }, function(response) {
                        $button.prop('disabled', false);
                        if (response.success) {
                            var html = '<h4>Test Results:</h4><ul>';
                            $.each(response.data.results, function(endpoint, result) {
                                var status = result.success ? 
                                    '<span style="color: green;">✓ Success</span>' : 
                                    '<span style="color: red;">✗ Failed</span>';
                                html += '<li><strong>' + endpoint + ':</strong> ' + status + '</li>';
                            });
                            html += '</ul>';
                            $('#test-results').html(html);
                        } else {
                            $('#test-results').html('<p style="color: red;">Tests failed</p>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Render API section description
     */
    public static function render_api_section() {
        echo '<p>' . __('Configure the connection to your Laravel backend API.', 'aroi-laravel') . '</p>';
    }
    
    /**
     * Render API URL field
     */
    public static function render_api_url_field() {
        $value = get_option('aroi_laravel_api_url', 'https://aroiasia.no/laravel-admin/api/v1');
        ?>
        <input type="url" 
               name="aroi_laravel_api_url" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php _e('Enter the base URL for your Laravel API (without trailing slash)', 'aroi-laravel'); ?>
        </p>
        <?php
    }
    
    /**
     * Render API timeout field
     */
    public static function render_api_timeout_field() {
        $value = get_option('aroi_laravel_api_timeout', 30);
        ?>
        <input type="number" 
               name="aroi_laravel_api_timeout" 
               value="<?php echo esc_attr($value); ?>" 
               min="5" 
               max="300" 
               class="small-text" />
        <p class="description">
            <?php _e('Maximum time to wait for API responses', 'aroi-laravel'); ?>
        </p>
        <?php
    }
    
    /**
     * Render cache duration field
     */
    public static function render_cache_duration_field() {
        $value = get_option('aroi_laravel_cache_duration', 300);
        ?>
        <input type="number" 
               name="aroi_laravel_cache_duration" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="3600" 
               class="small-text" />
        <p class="description">
            <?php _e('How long to cache API responses (0 to disable cache)', 'aroi-laravel'); ?>
        </p>
        <?php
    }
    
    /**
     * Render recent orders table
     */
    private static function render_recent_orders_table() {
        $args = array(
            'post_type' => 'shop_order',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $orders = get_posts($args);
        
        if (empty($orders)) {
            echo '<p>' . __('No orders found.', 'aroi-laravel') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order', 'aroi-laravel'); ?></th>
                    <th><?php _e('Date', 'aroi-laravel'); ?></th>
                    <th><?php _e('Status', 'aroi-laravel'); ?></th>
                    <th><?php _e('Pickup Time', 'aroi-laravel'); ?></th>
                    <th><?php _e('Laravel ID', 'aroi-laravel'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order_post): 
                    $order = wc_get_order($order_post->ID);
                    $pickup_time = get_post_meta($order->get_id(), 'hentes_kl', true);
                    $laravel_id = $order->get_meta('_laravel_order_id');
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                #<?php echo $order->get_order_number(); ?>
                            </a>
                        </td>
                        <td><?php echo $order->get_date_created()->format('Y-m-d H:i'); ?></td>
                        <td><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                        <td><?php echo $pickup_time ?: '-'; ?></td>
                        <td><?php echo $laravel_id ?: '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}

// Add AJAX handlers
add_action('wp_ajax_aroi_clear_cache', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'aroi_clear_cache')) {
        wp_die('Security check failed');
    }
    
    $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
    $api->clear_cache();
    
    wp_send_json_success(array('message' => __('Cache cleared successfully', 'aroi-laravel')));
});

add_action('wp_ajax_aroi_migrate_addons', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'aroi_migrate_addons')) {
        wp_die('Security check failed');
    }
    
    $migrated = Aroi_Product_Addons::migrate_all_dsk_addons();
    
    wp_send_json_success(array(
        'message' => sprintf(__('Migrated %d products', 'aroi-laravel'), $migrated)
    ));
});

add_action('wp_ajax_aroi_test_api', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'aroi_test_api')) {
        wp_die('Security check failed');
    }
    
    $site_id = Aroi_Laravel_Integration::get_site_id();
    $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
    
    $tests = array(
        'Location Info' => "/wordpress/location/{$site_id}",
        'Opening Hours' => "/wordpress/location/{$site_id}/opening-hours",
        'Is Open' => "/wordpress/location/{$site_id}/is-open",
        'Delivery Time' => "/wordpress/location/{$site_id}/delivery-time",
    );
    
    $results = array();
    
    foreach ($tests as $name => $endpoint) {
        $response = $api->get($endpoint, false);
        $results[$name] = array(
            'success' => $response !== null,
            'data' => $response
        );
    }
    
    wp_send_json_success(array('results' => $results));
});