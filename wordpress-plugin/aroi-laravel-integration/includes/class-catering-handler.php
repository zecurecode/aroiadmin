<?php
/**
 * Catering Handler Class
 *
 * Handles all catering-related functionality for the WordPress frontend
 *
 * @package Aroi_Laravel_Integration
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Catering_Handler {
    
    /**
     * API client instance
     *
     * @var Aroi_API_Client
     */
    private $api_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new Aroi_API_Client();
        
        // Register shortcodes
        add_shortcode('aroi_catering_form', array($this, 'render_catering_form'));
        
        // Register AJAX handlers
        add_action('wp_ajax_aroi_check_catering_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_aroi_check_catering_availability', array($this, 'ajax_check_availability'));
        
        add_action('wp_ajax_aroi_get_catering_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_nopriv_aroi_get_catering_products', array($this, 'ajax_get_products'));
        
        add_action('wp_ajax_aroi_submit_catering_order', array($this, 'ajax_submit_order'));
        add_action('wp_ajax_nopriv_aroi_submit_catering_order', array($this, 'ajax_submit_order'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Handle WooCommerce hooks for catering orders
        add_action('woocommerce_checkout_create_order', array($this, 'add_catering_meta_to_order'), 10, 2);
        add_action('woocommerce_payment_complete', array($this, 'handle_catering_payment_complete'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'aroi_catering_form')) {
            // Enqueue datepicker
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            
            // Enqueue catering scripts
            wp_enqueue_script(
                'aroi-catering',
                AROI_LARAVEL_PLUGIN_URL . 'assets/js/aroi-catering.js',
                array('jquery', 'jquery-ui-datepicker'),
                AROI_LARAVEL_VERSION,
                true
            );
            
            // Enqueue catering styles
            wp_enqueue_style(
                'aroi-catering',
                AROI_LARAVEL_PLUGIN_URL . 'assets/css/aroi-catering.css',
                array(),
                AROI_LARAVEL_VERSION
            );
            
            // Localize script
            wp_localize_script('aroi-catering', 'aroi_catering', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aroi_catering_nonce'),
                'site_id' => $this->get_current_site_id(),
                'currency' => get_woocommerce_currency_symbol(),
                'date_format' => 'yy-mm-dd',
                'min_date' => '+2d',
                'texts' => array(
                    'loading' => __('Laster...', 'aroi-laravel'),
                    'error' => __('En feil oppstod. Vennligst prøv igjen.', 'aroi-laravel'),
                    'date_unavailable' => __('Denne datoen er ikke tilgjengelig for catering.', 'aroi-laravel'),
                    'select_location' => __('Vennligst velg en avdeling først.', 'aroi-laravel'),
                    'min_guests' => __('Minimum antall gjester: ', 'aroi-laravel'),
                    'min_amount' => __('Minimum bestillingsbeløp: ', 'aroi-laravel')
                )
            ));
        }
    }
    
    /**
     * Render catering form shortcode
     */
    public function render_catering_form($atts) {
        $atts = shortcode_atts(array(
            'show_location_selector' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div id="aroi-catering-form" class="aroi-catering-container">
            <?php if ($atts['show_location_selector'] === 'yes') : ?>
                <div class="aroi-catering-step" id="step-location">
                    <h2><?php _e('Velg avdeling', 'aroi-laravel'); ?></h2>
                    <div class="aroi-location-selector">
                        <?php echo $this->render_location_selector(); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="aroi-catering-step" id="step-details" style="display: none;">
                <h2><?php _e('Cateringdetaljer', 'aroi-laravel'); ?></h2>
                <form id="aroi-catering-details-form">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="delivery_date"><?php _e('Leveringsdato *', 'aroi-laravel'); ?></label>
                            <input type="text" class="form-control" id="delivery_date" name="delivery_date" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="delivery_time"><?php _e('Leveringstidspunkt *', 'aroi-laravel'); ?></label>
                            <select class="form-control" id="delivery_time" name="delivery_time" required>
                                <option value="">Velg tidspunkt</option>
                                <?php echo $this->generate_time_options(); ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_address"><?php _e('Leveringsadresse *', 'aroi-laravel'); ?></label>
                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="number_of_guests"><?php _e('Antall gjester *', 'aroi-laravel'); ?></label>
                            <input type="number" class="form-control" id="number_of_guests" name="number_of_guests" min="1" required>
                            <small class="form-text text-muted" id="min-guests-text"></small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact_phone"><?php _e('Kontakttelefon *', 'aroi-laravel'); ?></label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="contact_name"><?php _e('Kontaktperson *', 'aroi-laravel'); ?></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact_email"><?php _e('E-post *', 'aroi-laravel'); ?></label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="special_requirements"><?php _e('Spesielle krav (allergier, preferanser)', 'aroi-laravel'); ?></label>
                        <textarea class="form-control" id="special_requirements" name="special_requirements" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="catering_notes"><?php _e('Andre kommentarer', 'aroi-laravel'); ?></label>
                        <textarea class="form-control" id="catering_notes" name="catering_notes" rows="3"></textarea>
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="continue-to-products">
                        <?php _e('Fortsett til menyvalg', 'aroi-laravel'); ?>
                    </button>
                </form>
            </div>
            
            <div class="aroi-catering-step" id="step-products" style="display: none;">
                <h2><?php _e('Velg produkter', 'aroi-laravel'); ?></h2>
                <div id="catering-products-container">
                    <!-- Products will be loaded here via AJAX -->
                </div>
                <div class="catering-total">
                    <h3><?php _e('Total:', 'aroi-laravel'); ?> <span id="catering-total-amount">0</span></h3>
                    <small class="form-text text-muted" id="min-amount-text"></small>
                </div>
                <button type="button" class="btn btn-secondary" id="back-to-details">
                    <?php _e('Tilbake', 'aroi-laravel'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="submit-catering-order">
                    <?php _e('Send bestilling', 'aroi-laravel'); ?>
                </button>
            </div>
            
            <div class="aroi-catering-step" id="step-confirmation" style="display: none;">
                <div class="alert alert-success">
                    <h2><?php _e('Takk for din bestilling!', 'aroi-laravel'); ?></h2>
                    <p><?php _e('Vi har mottatt din cateringbestilling og sender deg en bekreftelse på e-post.', 'aroi-laravel'); ?></p>
                    <p><?php _e('Du vil bli videresendt til betaling om kort tid...', 'aroi-laravel'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render location selector
     */
    private function render_location_selector() {
        $locations = $this->get_active_locations();
        
        ob_start();
        ?>
        <div class="row">
            <?php foreach ($locations as $location) : ?>
                <div class="col-md-4 mb-3">
                    <div class="location-card" data-site-id="<?php echo esc_attr($location['site_id']); ?>">
                        <h4><?php echo esc_html($location['name']); ?></h4>
                        <?php if (!empty($location['address'])) : ?>
                            <p><?php echo esc_html($location['address']); ?></p>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary select-location" 
                                data-site-id="<?php echo esc_attr($location['site_id']); ?>">
                            <?php _e('Velg denne avdelingen', 'aroi-laravel'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get active locations from API
     */
    private function get_active_locations() {
        // This should ideally come from an API endpoint
        // For now, using hardcoded values from documentation
        return array(
            array('site_id' => 7, 'name' => 'Namsos', 'address' => ''),
            array('site_id' => 4, 'name' => 'Lade', 'address' => ''),
            array('site_id' => 6, 'name' => 'Moan', 'address' => ''),
            array('site_id' => 5, 'name' => 'Gramyra', 'address' => ''),
            array('site_id' => 10, 'name' => 'Frosta', 'address' => ''),
            array('site_id' => 11, 'name' => 'Hell', 'address' => ''),
            array('site_id' => 12, 'name' => 'Steinkjer', 'address' => '')
        );
    }
    
    /**
     * Generate time options
     */
    private function generate_time_options() {
        $options = '';
        for ($hour = 10; $hour <= 20; $hour++) {
            for ($min = 0; $min < 60; $min += 30) {
                $time = sprintf('%02d:%02d', $hour, $min);
                $options .= '<option value="' . $time . '">' . $time . '</option>';
            }
        }
        return $options;
    }
    
    /**
     * AJAX handler for checking availability
     */
    public function ajax_check_availability() {
        check_ajax_referer('aroi_catering_nonce', 'nonce');
        
        $site_id = intval($_POST['site_id']);
        $date = sanitize_text_field($_POST['date']);
        
        $response = $this->api_client->post("/catering/location/{$site_id}/check-availability", array(
            'date' => $date
        ));
        
        wp_send_json($response);
    }
    
    /**
     * AJAX handler for getting products
     */
    public function ajax_get_products() {
        check_ajax_referer('aroi_catering_nonce', 'nonce');
        
        $site_id = intval($_POST['site_id']);
        
        // Get catering settings first
        $settings = $this->api_client->get("/catering/location/{$site_id}/settings");
        
        if (!$settings['success']) {
            wp_send_json_error('Failed to load catering settings');
            return;
        }
        
        $location = $settings['data']['location'];
        
        // Get products from WooCommerce API
        if (!empty($location['woocommerce_url']) && !empty($location['woocommerce_key'])) {
            $products = $this->get_woocommerce_products($location);
            wp_send_json_success(array(
                'products' => $products,
                'settings' => $settings['data']['settings']
            ));
        } else {
            wp_send_json_error('WooCommerce configuration missing for this location');
        }
    }
    
    /**
     * Get products from WooCommerce API
     */
    private function get_woocommerce_products($location) {
        $consumer_key = $location['woocommerce_key'];
        $consumer_secret = $location['woocommerce_secret'];
        $store_url = $location['woocommerce_url'];
        
        // Initialize WooCommerce API client
        $response = wp_remote_get(
            $store_url . '/wp-json/wc/v3/products?category=catering&per_page=100',
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
                )
            )
        );
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $products = json_decode(wp_remote_retrieve_body($response), true);
        
        // Filter and format products for catering
        $catering_products = array();
        foreach ($products as $product) {
            if ($product['status'] === 'publish' && $product['in_stock']) {
                $catering_products[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'description' => strip_tags($product['short_description']),
                    'image' => !empty($product['images']) ? $product['images'][0]['src'] : '',
                    'min_quantity' => 1 // This could be a custom field
                );
            }
        }
        
        return $catering_products;
    }
    
    /**
     * AJAX handler for submitting order
     */
    public function ajax_submit_order() {
        check_ajax_referer('aroi_catering_nonce', 'nonce');
        
        // Validate input
        $required_fields = array('site_id', 'delivery_date', 'delivery_time', 'delivery_address', 
                                'number_of_guests', 'contact_name', 'contact_phone', 'contact_email', 
                                'products', 'total_amount');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Missing required field: {$field}");
                return;
            }
        }
        
        // Create WooCommerce order first
        $order = wc_create_order();
        
        // Add products to order
        $products = json_decode(stripslashes($_POST['products']), true);
        foreach ($products as $product) {
            $order->add_product(wc_get_product($product['id']), $product['quantity']);
        }
        
        // Set billing details
        $name_parts = explode(' ', sanitize_text_field($_POST['contact_name']), 2);
        $order->set_billing_first_name($name_parts[0]);
        $order->set_billing_last_name(isset($name_parts[1]) ? $name_parts[1] : '');
        $order->set_billing_email(sanitize_email($_POST['contact_email']));
        $order->set_billing_phone(sanitize_text_field($_POST['contact_phone']));
        
        // Add catering meta data
        $order->update_meta_data('_is_catering_order', 'yes');
        $order->update_meta_data('_catering_site_id', intval($_POST['site_id']));
        $order->update_meta_data('_catering_delivery_date', sanitize_text_field($_POST['delivery_date']));
        $order->update_meta_data('_catering_delivery_time', sanitize_text_field($_POST['delivery_time']));
        $order->update_meta_data('_catering_delivery_address', sanitize_textarea_field($_POST['delivery_address']));
        $order->update_meta_data('_catering_number_of_guests', intval($_POST['number_of_guests']));
        $order->update_meta_data('_catering_special_requirements', sanitize_textarea_field($_POST['special_requirements']));
        $order->update_meta_data('_catering_notes', sanitize_textarea_field($_POST['catering_notes']));
        
        // Calculate totals
        $order->calculate_totals();
        
        // Save order
        $order->save();
        
        // Send to Laravel API
        $api_response = $this->api_client->post('/catering/orders', array(
            'site' => intval($_POST['site_id']),
            'fornavn' => $name_parts[0],
            'etternavn' => isset($name_parts[1]) ? $name_parts[1] : '',
            'telefon' => sanitize_text_field($_POST['contact_phone']),
            'epost' => sanitize_email($_POST['contact_email']),
            'delivery_date' => sanitize_text_field($_POST['delivery_date']),
            'delivery_time' => sanitize_text_field($_POST['delivery_time']),
            'delivery_address' => sanitize_textarea_field($_POST['delivery_address']),
            'number_of_guests' => intval($_POST['number_of_guests']),
            'ordreid' => $order->get_id(),
            'paymentmethod' => 'pending',
            'special_requirements' => sanitize_textarea_field($_POST['special_requirements']),
            'catering_notes' => sanitize_textarea_field($_POST['catering_notes']),
            'total_amount' => floatval($_POST['total_amount'])
        ));
        
        if ($api_response['success']) {
            // Get checkout URL
            $checkout_url = $order->get_checkout_payment_url();
            
            wp_send_json_success(array(
                'order_id' => $order->get_id(),
                'checkout_url' => $checkout_url
            ));
        } else {
            // Delete the order if API call failed
            $order->delete(true);
            wp_send_json_error('Failed to create catering order');
        }
    }
    
    /**
     * Add catering meta to WooCommerce order
     */
    public function add_catering_meta_to_order($order, $data) {
        if ($order->get_meta('_is_catering_order') === 'yes') {
            // Additional processing if needed
        }
    }
    
    /**
     * Handle payment complete for catering orders
     */
    public function handle_catering_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order->get_meta('_is_catering_order') === 'yes') {
            // Mark as paid in Laravel
            $this->api_client->post('/catering/orders/mark-paid', array(
                'order_id' => $order_id
            ));
        }
    }
    
    /**
     * Get current site ID
     */
    private function get_current_site_id() {
        if (is_multisite()) {
            return get_current_blog_id();
        }
        return get_option('aroi_default_site_id', 7); // Default to Namsos
    }
}

// Initialize the handler
new Aroi_Catering_Handler();