<?php
/**
 * Order Handler for WooCommerce Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Order_Handler {
    
    /**
     * Create order in Laravel when WooCommerce order is created
     */
    public static function create_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $site_id = Aroi_Laravel_Integration::get_site_id();
        
        // Prepare order data
        $order_data = array(
            'fornavn' => $order->get_billing_first_name(),
            'etternavn' => $order->get_billing_last_name(),
            'telefon' => $order->get_billing_phone(),
            'ordreid' => $order_id,
            'epost' => $order->get_billing_email(),
            'site' => $site_id,
            'ordrestatus' => 0,
            'curl' => 0,
            'paid' => 0
        );
        
        // Send to Laravel API
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->post('/orders', $order_data);
        
        if ($response) {
            // Store Laravel order ID in WooCommerce meta
            if (isset($response['id'])) {
                $order->update_meta_data('_laravel_order_id', $response['id']);
                $order->save();
            }
            
            // Log success
            $order->add_order_note(__('Ordre sendt til Laravel system', 'aroi-laravel'));
        } else {
            // Log error
            $order->add_order_note(__('Feil ved sending av ordre til Laravel system', 'aroi-laravel'));
        }
    }
    
    /**
     * Mark order as paid in Laravel
     */
    public static function mark_order_paid($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $site_id = Aroi_Laravel_Integration::get_site_id();
        
        // Prepare data
        $data = array(
            'order_id' => $order_id,
            'site_id' => $site_id
        );
        
        // Send to Laravel API
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->post('/orders/mark-paid', $data);
        
        if ($response) {
            // Trigger order processing
            $api->post('/process-orders', array());
            
            // Log success
            $order->add_order_note(__('Betaling registrert i Laravel system', 'aroi-laravel'));
        } else {
            // Log error
            $order->add_order_note(__('Feil ved registrering av betaling i Laravel system', 'aroi-laravel'));
        }
    }
    
    /**
     * Display pickup time selection field
     */
    public static function display_pickup_time_field($checkout) {
        date_default_timezone_set('Europe/Oslo');
        
        $site_id = Aroi_Laravel_Integration::get_site_id();
        
        // Get location hours
        $open_time = Aroi_Location_Functions::get_open_time($site_id);
        $close_time = Aroi_Location_Functions::get_close_time($site_id);
        $is_open = Aroi_Location_Functions::is_open($site_id);
        $delivery_time = Aroi_Location_Functions::get_delivery_time($site_id);
        
        if (!$open_time || !$close_time) {
            return;
        }
        
        $open_timestamp = strtotime($open_time);
        $close_timestamp = strtotime($close_time);
        $current_time = strtotime('now');
        
        $options = array();
        
        // Check if closed
        if (!$is_open || $current_time > $close_timestamp || $current_time < $open_timestamp) {
            echo '<h5>' . __('Vognen er nå stengt. Du kan fortsatt bestille, og maten er klar til henting på første ledige tidspunkt.', 'aroi-laravel') . '</h5>';
            
            // Start from opening time
            $next_available = $open_timestamp;
            
            // If we're past closing time, move to tomorrow's opening
            if ($current_time > $close_timestamp) {
                $next_available = strtotime('tomorrow ' . $open_time);
            }
            
            $options[date('H:i', $next_available)] = date('H:i', $next_available);
            
            // Round to next 15 minutes
            $next_available = ceil($next_available / (15 * 60)) * (15 * 60);
            
            // Add time slots
            while ($next_available <= $close_timestamp && count($options) < 20) {
                $value = date('H:i', $next_available);
                $options[$value] = $value;
                $next_available = strtotime('+15 minutes', $next_available);
            }
        } else {
            // Open - calculate next available pickup time
            echo '<h5>' . sprintf(__('Det tar omtrent %d minutter før din bestilling er klar for henting', 'aroi-laravel'), $delivery_time) . '</h5>';
            
            $next_available = strtotime("+{$delivery_time} minutes", $current_time);
            
            // Add first option
            $options[date('H:i', $next_available)] = date('H:i', $next_available);
            
            // Round to next 15 minutes
            $next_available = ceil($next_available / (15 * 60)) * (15 * 60);
            
            // Add time slots until closing
            while ($next_available <= $close_timestamp) {
                $value = date('H:i', $next_available);
                $options[$value] = $value;
                $next_available = strtotime('+15 minutes', $next_available);
            }
        }
        
        // Add notice before field
        add_action('woocommerce_before_order_notes', array(__CLASS__, 'display_notice'), 5);
        
        // Add the pickup time field
        woocommerce_form_field('hentes_kl', array(
            'type' => 'select',
            'class' => array('wps-drop'),
            'label' => __('Hentetidspunkt', 'aroi-laravel'),
            'required' => true,
            'options' => $options,
        ), $checkout->get_value('hentes_kl'));
    }
    
    /**
     * Display notice about order processing
     */
    public static function display_notice() {
        date_default_timezone_set('Europe/Oslo');
        
        $site_id = Aroi_Laravel_Integration::get_site_id();
        $open_time = Aroi_Location_Functions::get_open_time($site_id);
        $close_time = Aroi_Location_Functions::get_close_time($site_id);
        $is_open = Aroi_Location_Functions::is_open($site_id);
        
        if (!$is_open) {
            echo '<h3 style="color:red; text-align:center;">' . 
                 __('Vognen er stengt.<br> Du kan fortsatt bestille for neste dag.', 'aroi-laravel') . 
                 '</h3>';
        }
        
        $current_time = strtotime('now');
        $open_timestamp = $open_time ? strtotime($open_time) : 0;
        $close_timestamp = $close_time ? strtotime($close_time) : 0;
        
        if (!$is_open || $current_time > $close_timestamp || $current_time < $open_timestamp) {
            wc_print_notice(
                __('Bestillinger som kommer utenfor vår åpningstid blir gjennomgått så snart butikken åpner. Så snart butikkens medarbeidere har klargjort maten, får du beskjed på e-mail og sms.', 'aroi-laravel'),
                'success'
            );
        }
    }
    
    /**
     * Save pickup time to order
     */
    public static function save_pickup_time($order_id) {
        if (isset($_POST['hentes_kl']) && !empty($_POST['hentes_kl'])) {
            $pickup_time = sanitize_text_field($_POST['hentes_kl']);
            update_post_meta($order_id, 'hentes_kl', $pickup_time);
            
            // Also save to order notes for visibility
            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(sprintf(__('Hentetidspunkt: %s', 'aroi-laravel'), $pickup_time));
            }
        }
    }
    
    /**
     * Display pickup time in admin
     */
    public static function display_pickup_time_admin($order) {
        $pickup_time = get_post_meta($order->get_id(), 'hentes_kl', true);
        if ($pickup_time) {
            echo '<p><strong>' . __('Hentes kl', 'aroi-laravel') . ':</strong> ' . esc_html($pickup_time) . '</p>';
        }
    }
    
    /**
     * Add pickup time to email
     */
    public static function add_pickup_time_to_email($keys) {
        $keys['hentes_kl:'] = 'hentes_kl';
        return $keys;
    }
}