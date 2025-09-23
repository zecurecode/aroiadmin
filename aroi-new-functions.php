<?php
/**
 * New functions.php using Aroi Laravel Integration Plugin
 * This replaces the old functions.php and adminfunctions.php
 *
 * Prerequisites:
 * - Aroi Laravel Integration plugin must be installed and activated
 * - WooCommerce must be active
 * - Laravel admin system must be running and accessible
 */

// Include parent theme styles (LaFka theme functionality)
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
function theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );

    if (is_rtl()) {
        wp_enqueue_style('parent-rtl', get_template_directory_uri() . '/rtl.css');
        wp_enqueue_style('child-rtl',
            get_stylesheet_directory_uri() . '/rtl.css',
            array('parent-rtl')
        );
    }

    wp_enqueue_script('child-lafka-front',
        get_stylesheet_directory_uri() . '/js/lafka-front.js',
        array('lafka-front'),
        false,
        true
    );
}

// Remove zoom on single image product
function remove_image_zoom_support() {
    remove_theme_support('wc-product-gallery-zoom');
}
add_action('wp', 'remove_image_zoom_support', 100);

// Remove click on single image product
function e12_remove_product_image_link($html, $post_id) {
    return preg_replace("!<(a|/a).*?>!", '', $html);
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'e12_remove_product_image_link', 10, 2);

// Remove additional information tab
add_filter('woocommerce_product_tabs', 'woo_remove_product_tabs', 98);
function woo_remove_product_tabs($tabs) {
    unset($tabs['additional_information']);
    return $tabs;
}

// Show hidden meta fields
add_filter('is_protected_meta', '__return_false');

/**
 * Site Management Functions (using Laravel API)
 */

// Get the current site ID
function getCaller() {
    return get_current_blog_id();
}

// Get site license (using Laravel API)
function getSiteLicense($site_id = null) {
    if (!$site_id) {
        $site_id = getCaller();
    }

    if (class_exists('Aroi_Order_Handler')) {
        return Aroi_Order_Handler::get_site_license($site_id);
    }

    // Fallback to local mapping if plugin not active
    $licenses = array(
        7 => 6714,   // Namsos
        4 => 12381,  // Lade
        6 => 5203,   // Moan
        5 => 6715,   // Gramyra
        10 => 14780, // Frosta
        13 => 30221, // Steinkjer
        15 => 14946, // Malvik
    );

    return $licenses[$site_id] ?? null;
}

/**
 * Opening Hours and Delivery Functions (using Laravel API)
 */

// Get delivery time for current site
function gettid_function($site_id = null) {
    if (!$site_id) {
        $site_id = getCaller();
    }

    if (class_exists('Aroi_Laravel_API')) {
        $api = new Aroi_Laravel_API();
        $delivery_data = $api->get_delivery_time($site_id);
        return $delivery_data['delivery_time'] ?? 30;
    }

    // Fallback delivery time
    return 30;
}

// Opening hours shortcode with enhanced formatting
add_shortcode('gettid2', 'gettid_function2');
function gettid_function2($atts) {
    $atts = shortcode_atts(array(
        'site' => getCaller()
    ), $atts, 'gettid2');

    if (class_exists('Aroi_Checkout_Manager')) {
        $checkout_manager = new Aroi_Checkout_Manager();
        return $checkout_manager->get_formatted_opening_hours($atts['site']);
    }

    return __('Opening hours not available', 'aroi-laravel-integration');
}

// Check if site is open (backward compatibility)
function isOpen($site_id = null) {
    if (!$site_id) {
        $site_id = getCaller();
    }

    if (class_exists('Aroi_Checkout_Manager')) {
        return Aroi_Checkout_Manager::is_open($site_id);
    }

    return false;
}

/**
 * Product Addons Management
 * Keep existing addon functionality as it's WooCommerce-specific
 */

// Update product addons when saving products
add_action('save_post', 'update_product_addons', 30, 3);
function update_product_addons($post_id, $post, $update) {
    if ($post->post_status != 'publish' || $post->post_type != 'product') {
        return;
    }

    if (!$product = wc_get_product($post)) {
        return;
    }

    $product_addons = get_dsk_product_addons($post_id);

    if ($update == false) {
        add_post_meta($post_id, '_product_addons', $product_addons);
    } else {
        update_post_meta($post_id, '_product_addons', $product_addons);
    }
}

// Convert DSK addons to WooCommerce format
function get_dsk_product_addons($product_id) {
    $product_addons = array();
    $product_addons_dsk = get_post_meta($product_id, 'dsk_addons', true);
    $product_addons_dsk = json_decode($product_addons_dsk, true);

    if (!empty($product_addons_dsk)) {
        $addonsIndex = 0;

        foreach ($product_addons_dsk as $key => $addon) {
            $addon_options = array();

            foreach ($addon as $addonKey => $addonValue) {
                $label = sanitize_text_field(stripslashes($addonKey));
                $price = '';

                if (!empty($addonValue)) {
                    $price = wc_format_decimal(sanitize_text_field(stripslashes($addonValue)));
                }

                $addon_options[] = array(
                    'label' => $label,
                    'image' => '',
                    'price' => $price,
                    'min' => '',
                    'max' => '',
                    'default' => '',
                );
            }

            $addon_required = (strpos($key, '*') !== false) ? 1 : 0;

            $data = array(
                'name' => sanitize_text_field(stripslashes($key)),
                'limit' => '',
                'description' => '',
                'type' => 'radiobutton',
                'position' => absint($addonsIndex),
                'variations' => 0,
                'attribute' => 0,
                'options' => $addon_options,
                'required' => $addon_required,
            );

            $product_addons[] = $data;
            $addonsIndex++;
        }

        uasort($product_addons, 'addons_cmp');
    }

    return $product_addons;
}

// Addon comparison for sorting
function addons_cmp($a, $b) {
    if ($a['position'] == $b['position']) {
        return 0;
    }
    return ($a['position'] < $b['position']) ? -1 : 1;
}

/**
 * Navigation Helper for Product Pages
 */
add_filter('woocommerce_after_add_to_cart_button', 'dsk_show_arrows', 10, 1);
add_filter('woocommerce_before_single_product', 'dsk_show_arrows', 10, 1);
function dsk_show_arrows() {
    // Get parent product categories on single product pages
    $terms = wp_get_post_terms(get_the_id(), 'product_cat', array('include_children' => false));

    // Get the first main product category (not a child one)
    $term = reset($terms);
    if ($term) {
        $term_link = get_term_link($term->term_id, 'product_cat');
        echo '<h2 class="dsklink"><a href="' . esc_url($term_link) . '">';
        echo '<i class="fas fa-angle-left" style="font-size:48px;color:#8a3794"></i>';
        echo '</a></h2>';
    }
}

/**
 * Plugin Dependency Check
 */
function aroi_check_plugin_dependency() {
    if (!class_exists('AroiLaravelIntegration')) {
        add_action('admin_notices', 'aroi_plugin_missing_notice');
        return false;
    }
    return true;
}

function aroi_plugin_missing_notice() {
    echo '<div class="notice notice-error"><p>';
    echo __('This theme requires the "Aroi Laravel Integration" plugin to be installed and activated.', 'aroi-laravel-integration');
    echo '</p></div>';
}

// Check dependency on admin pages
add_action('admin_init', 'aroi_check_plugin_dependency');

/**
 * Cleanup: Remove old functions that are now handled by the plugin
 *
 * The following functions are now handled by the Aroi Laravel Integration plugin:
 * - Order creation and payment handling (via WooCommerce hooks in plugin)
 * - Pickup time field (via Aroi_Checkout_Manager)
 * - Opening hours notice (via Aroi_Checkout_Manager)
 * - Direct database connections (replaced with Laravel API calls)
 * - Hard-coded license mappings (moved to Laravel and plugin)
 *
 * Old functions removed:
 * - your_order_details() - replaced by Aroi_Order_Handler::handle_new_order()
 * - orderIsPaid() - replaced by Aroi_Order_Handler::handle_payment_complete()
 * - action_woocommerce_before_order_notes() - replaced by Aroi_Checkout_Manager
 * - All database() calls - replaced by Laravel API calls
 * - All hard-coded switch statements - moved to Laravel system
 */