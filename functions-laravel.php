<?php
/**
 * Laravel-Compatible Functions for WordPress/WooCommerce Integration
 * 
 * This file replaces the old functions.php to work with Laravel backend
 * All order processing and database operations go through Laravel API
 */

// Include our Laravel-compatible time functions
include('adminfunctions-laravel.php');

// Generate shortcodes...
add_shortcode('gettid', 'gettid_function');
add_shortcode('gettid2', 'gettid_function2');

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'parent-style' )
    );

    if ( is_rtl() ) {
        wp_enqueue_style( 'parent-rtl', get_template_directory_uri() . '/rtl.css' );
        wp_enqueue_style( 'child-rtl',
            get_stylesheet_directory_uri() . '/rtl.css',
            array( 'parent-rtl' )
        );
    }

    wp_enqueue_script( 'child-lafka-front',
        get_stylesheet_directory_uri() . '/js/lafka-front.js',
        array( 'lafka-front' ),
        false,
        true
    );
}

// Remove zoom on single image product
function remove_image_zoom_support() {
    remove_theme_support( 'wc-product-gallery-zoom' );
}
add_action( 'wp', 'remove_image_zoom_support', 100 );

// Remove click on single image product
function e12_remove_product_image_link( $html, $post_id ) {
    return preg_replace( "!<(a|/a).*?>!", '', $html );
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'e12_remove_product_image_link', 10, 2 );
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

// Remove additional information tab
function woo_remove_product_tabs( $tabs ) {
    unset( $tabs['additional_information'] );
    return $tabs;
}

// Show hidden meta fields
add_filter( 'is_protected_meta', '__return_false' ); 

// Get the ID from the calling blog
function getCaller(){
    $siteid = get_current_blog_id();
    return $siteid;
}

/**
 * Get license by site ID
 */
function getLicenseBySiteId($siteId) {
    $response = callLaravelAPI("/wordpress/location/{$siteId}");
    return $response ? $response['license'] : 0;
}

/**AutoOrder Start**/ 

// Send order to Laravel when created
add_action( 'woocommerce_new_order', 'your_order_details',  1, 1  );
add_action( 'woocommerce_payment_complete', 'orderIsPaid',  1, 1  );

/**
 * Mark order as paid in Laravel
 */
function orderIsPaid($order_id){
    $caller = getCaller();
    $license = getLicenseBySiteId($caller);
    
    // Call Laravel API to mark order as paid
    $data = [
        'order_id' => $order_id,
        'site_id' => $caller
    ];
    
    $response = callLaravelAPI('/orders/mark-paid', 'POST', $data);
    
    if ($response) {
        // Trigger order processing
        callLaravelAPI('/process-orders', 'POST');
    }
}

/**
 * Create order in Laravel when WooCommerce order is created
 */
function your_order_details($order_id){
    $caller = getCaller();
    $license = getLicenseBySiteId($caller);
    
    $order = new WC_Order($order_id);
    $order_num = $order_id; 
    $fornavn = $order->get_billing_first_name();
    $etternavn = $order->get_billing_last_name();
    $telefon = $order->get_billing_phone();
    $epost = $order->get_billing_email();
    
    // Prepare order data for Laravel
    $orderData = [
        'fornavn' => $fornavn,
        'etternavn' => $etternavn,
        'telefon' => $telefon,
        'ordreid' => $order_num,
        'epost' => $epost,
        'site' => $caller,
        'ordrestatus' => 0,
        'curl' => 0,
        'paid' => 0
    ];
    
    // Send to Laravel API
    callLaravelAPI('/orders', 'POST', $orderData);
}

// Display notice before order notes
add_action('woocommerce_before_order_notes', 'print_donation_notice', 10 );
function print_donation_notice() {
    date_default_timezone_set('Europe/Oslo');
    
    $siteId = getCaller();
    $openTime = strtotime(getOpen($siteId));
    $closeTime = strtotime(getClose($siteId));
    $currentTime = strtotime('now');
    $status = getStatus($siteId);
    
    if($status == 0){
        echo "<h3 style='color:red; text-align:center;'>Vognen er stengt.<br> Du kan fortsatt bestille for neste dag.</h3>";
    }
    
    // Closed
    if( $currentTime > $closeTime || $currentTime <= $openTime || $status == 0) {
        wc_print_notice( sprintf(
            __("Bestillinger som kommer utenfor vår åpningstid blir gjennomgått så snart butikken åpner. Så snart butikkens medarbeidere har klargjort maten, får du beskjed på e-mail og sms.", "woocommerce"),
            '<strong>' . __("Information:", "woocommerce") . '</strong>',
            strip_tags( wc_price( WC()->cart->get_subtotal() * 0.03 ) )
        ), 'success' );
    }
}

// Product addon functions remain the same as they handle local WordPress data
function updateAddons($product_id){
    $addondata = 'a:1:{i:0;a:9:{s:4:"name";s:7:"SalatLO";s:5:"limit";s:0:"";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:8:"position";i:0;s:10:"variations";i:0;s:9:"attribute";i:0;s:7:"options";a:2:{i:0;a:6:{s:5:"label";s:5:"Agurk";s:5:"image";s:0:"";s:5:"price";s:2:"10";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"1";}i:1;a:6:{s:5:"label";s:5:"Tomat";s:5:"image";s:0:"";s:5:"price";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"0";}}s:8:"required";i:0;}}';
    $newtest = unserialize($addondata);

    if ( ! add_post_meta( $product_id, 'dsk_addons',$newtest, true)){
        update_post_meta($product_id, 'dsk_addons',$newtest);
    }
}

function showData(){
    $addondata = 'a:1:{i:0;a:9:{s:4:"name";s:7:"SalatLO";s:5:"limit";s:0:"";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:8:"position";i:0;s:10:"variations";i:0;s:9:"attribute";i:0;s:7:"options";a:2:{i:0;a:6:{s:5:"label";s:5:"Agurk";s:5:"image";s:0:"";s:5:"price";s:2:"10";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"1";}i:1;a:6:{s:5:"label";s:5:"Tomat";s:5:"image";s:0:"";s:5:"price";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"0";}}s:8:"required";i:0;}}';
    $newtest = unserialize($addondata);
    print_r($newtest);
}

add_action( 'save_post', 'update_product_addons', 30, 3 );
function update_product_addons( $post_id, $post, $update ) {
    if ($post->post_status != 'publish' || $post->post_type != 'product') {
        return;
    }

    if (!$product = wc_get_product( $post )) {
        return;
    }
    
    $toUpate = get_post_meta($post_id, 'dsk_addons_updated', true);
    
    if(1 == 1){
        $product_addons = get_dsk_product_addons( $post_id );
        if ( $update == false ) {
            add_post_meta( $post_id, '_product_addons', $product_addons );
            update_post_meta($post_id, 'dsk_addons_updated', 'First if');
        } else {
            update_post_meta( $post_id, '_product_addons', $product_addons );
            update_post_meta($post_id, 'dsk_addons_updated', 'Second if');
        }
    }
}

function get_dsk_product_addons( $product_id ) {
    $product_addons = array();
    
    $product_addons_dsk = get_post_meta( $product_id, 'dsk_addons', true );
    $product_addons_dsk = json_decode( $product_addons_dsk, true );
    
    if ( !empty( $product_addons_dsk ) ) {
        $addonsIndex = 0;
        foreach($product_addons_dsk as $key => $addon) {
            $addon_options = array();
            foreach($addon as $addonKey => $addonValue) {
                $label = sanitize_text_field( stripslashes( $addonKey ) );
                $image = '';
                $price = '';
                if ( !empty( $addonValue ) ) {
                    $price = wc_format_decimal( sanitize_text_field( stripslashes( $addonValue ) ) );
                }
                $min = '';
                $max = '';
                $default = '';
                $addon_options[] = array(
                    'label'   => $label,
                    'image'   => $image,
                    'price'   => $price,
                    'min'     => $min,
                    'max'     => $max,
                    'default' => $default,
                );
            }
            
            $addon_required = 0;
            if (strpos($key, '*') !== false) {
                $addon_required = 1;
            }
            
            $data                = array();
            $data['name']        = sanitize_text_field( stripslashes( $key ) );
            $data['limit']       = '';
            $data['description'] = '';
            $data['type']        = 'radiobutton';
            $data['position']    = absint( $addonsIndex );
            $data['variations']  = 0;
            $data['attribute']   = 0;
            $data['options']     = $addon_options;
            $data['required']    = $addon_required;
            
            $product_addons[] = $data;
            $addonsIndex = $addonsIndex + 1;
        }
        
        if($post_id != null){
            update_post_meta($post_id, 'dsk_addons_updated', 'yes');    
        }
    } else{
        update_post_meta($post_id, 'dsk_addons_updated', 'no');
    }
    
    uasort( $product_addons, 'addons_cmp' );
    return $product_addons;
}

function addons_cmp( $a, $b ) {
    if ( $a['position'] == $b['position'] ) {
        return 0;
    }
    return ( $a['position'] < $b['position'] ) ? -1 : 1;
}

// Pickup time selection
function action_woocommerce_before_order_notes( $checkout ) { 
    date_default_timezone_set('Europe/Oslo');
    
    $siteId = getCaller();
    $openTime = strtotime(getOpen($siteId));
    $closeTime = strtotime(getClose($siteId));
    $currentTime = strtotime('now');
    
    $makingtime = gettid_function($siteId);
    $maketimestr = " +$makingtime minutes";
    
    getNextOpen();
    
    // Closed
    if( $currentTime > $closeTime || $currentTime <= $openTime ) {
        echo "<h5>Vognen er nå stengt. Du kan fortsatt bestille, og maten er klar til henting på første ledige tidspunkt.</h5>";
        
        $asa_possible = $openTime;
        $options[date( 'H:i', $openTime )] = __( date( 'H:i', $openTime ), 'woocommerce');
        $asa_possible = ceil( $asa_possible / ( 15 * 60 ) ) * ( 15 * 60);
        
        while( $asa_possible <= $closeTime &&  $asa_possible >= $openTime ) {
            $value = date( 'H:i', $asa_possible );
            $options[$value] = $value;
            $asa_possible = strtotime( '+15 minutes', $asa_possible );
        }
    } else {
        echo "<h5>Det tar omtrent $makingtime minutter før din bestilling er klar for henting</h5>";
        
        $asa_possible = strtotime( $maketimestr, $currentTime );
        $options[date( 'H:i', $asa_possible )] = __( date( 'H:i', $asa_possible ), 'woocommerce');
        $asa_possible = ceil( $asa_possible / ( 15 * 60 ) ) * ( 15 * 60);
        
        while( $asa_possible <= $closeTime && $asa_possible >= $openTime ) {
            $value = date( 'H:i', $asa_possible );
            $options[$value] = $value;
            $asa_possible = strtotime( '+15 minutes', $asa_possible );
        }
    }

    // Add field
    woocommerce_form_field( 'hentes_kl', array(
        'type'          => 'select',
        'class'         => array( 'wps-drop' ),
        'label'         => __('Hentetidspunkt', 'woocommerce' ),
        'options'       => $options,
    ), $checkout->get_value( 'hentes_kl' ));
}

add_action( 'woocommerce_before_order_notes', 'action_woocommerce_before_order_notes', 10, 1 );

// Save pickup time
add_action('woocommerce_checkout_update_order_meta', 'wps_select_checkout_field_update_order_meta');
function wps_select_checkout_field_update_order_meta( $order_id ) {
   if ($_POST['hentes_kl']) update_post_meta( $order_id, 'hentes_kl', esc_attr($_POST['hentes_kl']));
}

// Display in admin
add_action( 'woocommerce_admin_order_data_after_billing_address', 'wps_select_checkout_field_display_admin_order_meta', 10, 1 );
function wps_select_checkout_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Hentes kl ').':</strong> ' . get_post_meta( $order->id, 'hentes_kl', true ) . '</p>';
}

// Add to email
add_filter('woocommerce_email_order_meta_keys', 'wps_select_order_meta_keys');
function wps_select_order_meta_keys( $keys ) {
    $keys['hentes_kl:'] = 'hentes_kl';
    return $keys;
}

// Show navigation arrows
add_filter('woocommerce_after_add_to_cart_button', 'dsk_show_arrows', 10, 1);
add_filter('woocommerce_before_single_product', 'dsk_show_arrows', 10, 1);
function dsk_show_arrows(){
    $terms = wp_get_post_terms( get_the_id(), 'product_cat', array( 'include_children' => false ) );
    $term = reset($terms);
    $term_link =  get_term_link( $term->term_id, 'product_cat' );
    echo '<h2 class="dsklink"><a href="'.$term_link.'"><i class="fas fa-angle-left" style="font-size:48px;color:#8a3794"></i></a></h2>';
}