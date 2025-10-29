<?php
/**
 * Aroi Child Theme Functions
 * CLEANED VERSION - Compatible with MultiSide Aroi Integration Plugin
 *
 * REMOVED (now handled by plugin):
 * - Order creation/payment hooks (woocommerce_new_order, woocommerce_payment_complete)
 * - Pickup time selector on checkout
 * - Opening hours notices
 * - Database order insertion
 * - getCaller() function (replaced by plugin's dynamic detection)
 *
 * KEPT (unique to theme):
 * - Theme styles enqueue
 * - Product image/zoom customization
 * - Product addons conversion system (dsk_addons → _product_addons)
 * - Product navigation arrows
 */

// ============================================
// THEME STYLES & SCRIPTS
// ============================================

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

// ============================================
// WOOCOMMERCE PRODUCT CUSTOMIZATION
// ============================================

/**
 * Remove image zoom on single product page
 */
add_action('wp', 'remove_image_zoom_support', 100);
function remove_image_zoom_support() {
    remove_theme_support('wc-product-gallery-zoom');
}

/**
 * Remove clickable links on product images
 */
add_filter('woocommerce_single_product_image_thumbnail_html', 'e12_remove_product_image_link', 10, 2);
function e12_remove_product_image_link($html, $post_id) {
    return preg_replace("!<(a|/a).*?>!", '', $html);
}

/**
 * Remove additional information tab
 */
add_filter('woocommerce_product_tabs', 'woo_remove_product_tabs', 98);
function woo_remove_product_tabs($tabs) {
    unset($tabs['additional_information']);
    return $tabs;
}

/**
 * Show hidden meta fields in admin
 */
add_filter('is_protected_meta', '__return_false');

// ============================================
// PRODUCT ADDONS CONVERSION SYSTEM
// ============================================

/**
 * Convert dsk_addons format to WooCommerce Product Addons format
 * This runs on product save to ensure compatibility with Product Addons plugin
 */
add_action('save_post', 'update_product_addons', 30, 3);
function update_product_addons($post_id, $post, $update) {

    if ($post->post_status != 'publish' || $post->post_type != 'product') {
        return;
    }

    if (!$product = wc_get_product($post)) {
        return;
    }

    // Get dsk_addons custom format
    $product_addons = get_dsk_product_addons($post_id);

    if (!empty($product_addons)) {
        if ($update == false) {
            add_post_meta($post_id, '_product_addons', $product_addons);
            update_post_meta($post_id, 'dsk_addons_updated', 'yes');
        } else {
            update_post_meta($post_id, '_product_addons', $product_addons);
            update_post_meta($post_id, 'dsk_addons_updated', 'yes');
        }
    } else {
        update_post_meta($post_id, 'dsk_addons_updated', 'no');
    }
}

/**
 * Convert dsk_addons JSON format to WooCommerce Product Addons array format
 *
 * Input format (dsk_addons): JSON string with addon groups
 * Output format (_product_addons): Array compatible with Product Addons plugin
 *
 * @param int $product_id Product ID
 * @return array Product addons in WooCommerce format
 */
function get_dsk_product_addons($product_id) {

    $product_addons = array();

    // Get custom dsk_addons meta field
    $product_addons_dsk = get_post_meta($product_id, 'dsk_addons', true);
    $product_addons_dsk = json_decode($product_addons_dsk, true);

    if (!empty($product_addons_dsk)) {

        $addonsIndex = 0;

        foreach($product_addons_dsk as $key => $addon) {

            $addon_options = array();

            // Convert each addon option
            foreach($addon as $addonKey => $addonValue) {

                $label = sanitize_text_field(stripslashes($addonKey));
                $image = '';
                $price = '';

                if (!empty($addonValue)) {
                    $price = wc_format_decimal(sanitize_text_field(stripslashes($addonValue)));
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

            // Check if addon is required (marked with *)
            $addon_required = 0;
            if (strpos($key, '*') !== false) {
                $addon_required = 1;
            }

            // Build addon group
            $data = array();
            $data['name']        = sanitize_text_field(stripslashes($key));
            $data['limit']       = '';
            $data['description'] = '';
            $data['type']        = 'radiobutton';
            $data['position']    = absint($addonsIndex);
            $data['variations']  = 0;
            $data['attribute']   = 0;
            $data['options']     = $addon_options;
            $data['required']    = $addon_required;

            // Add to array
            $product_addons[] = $data;

            $addonsIndex++;
        }

        // Sort by position
        uasort($product_addons, 'addons_cmp');
    }

    return $product_addons;
}

/**
 * Sort addons by position
 */
function addons_cmp($a, $b) {
    if ($a['position'] == $b['position']) {
        return 0;
    }
    return ($a['position'] < $b['position']) ? -1 : 1;
}

// ============================================
// PRODUCT NAVIGATION
// ============================================

/**
 * Show back arrow to category on single product page
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
        echo '<h2 class="dsklink"><a href="' . esc_url($term_link) . '"><i class="fas fa-angle-left" style="font-size:48px;color:#8a3794"></i></a></h2>';
    }
}

// ============================================
// LEGACY SUPPORT FUNCTIONS (Deprecated)
// ============================================

/**
 * DEPRECATED: Use Multiside_Aroi_Site_Config::get_current_site_id() instead
 *
 * This function is kept for backward compatibility only.
 * The plugin now handles site ID detection automatically.
 *
 * @deprecated 2.0.0 Use plugin's dynamic site detection
 * @return int Site ID
 */
function getCaller() {
    // Check if plugin is active and use its method
    if (class_exists('Multiside_Aroi_Site_Config')) {
        return Multiside_Aroi_Site_Config::get_current_site_id();
    }

    // Fallback to old method
    return get_current_blog_id();
}

// ============================================
// MIGRATION NOTES
// ============================================

/**
 * MIGRATION FROM OLD SYSTEM TO PLUGIN:
 *
 * 1. REMOVED FUNCTIONS (now in plugin):
 *    - your_order_details() → Multiside_Aroi_Order_Handler::on_order_created()
 *    - orderIsPaid() → Multiside_Aroi_Order_Handler::on_payment_complete()
 *    - print_donation_notice() → Multiside_Aroi_Checkout_Manager::display_opening_status_notice()
 *    - action_woocommerce_before_order_notes() → Multiside_Aroi_Checkout_Manager::display_pickup_time_selector()
 *
 * 2. REMOVED HOOKS (now in plugin):
 *    - woocommerce_new_order → Plugin handles order creation
 *    - woocommerce_payment_complete → Plugin handles payment and PCKasse/SMS
 *    - woocommerce_before_order_notes → Plugin handles pickup time selector
 *    - woocommerce_checkout_update_order_meta → Plugin saves hentes_kl
 *    - woocommerce_admin_order_data_after_billing_address → Plugin displays order meta
 *    - woocommerce_email_order_meta_keys → Plugin adds hentes_kl to emails
 *
 * 3. CONFIGURATION NOW DYNAMIC:
 *    - Site ID: Auto-detected from WordPress Multisite or database
 *    - PCKasse License: Fetched from database per site
 *    - SMS Credentials: Fetched from database or fallback
 *    - Opening Hours: From _apningstid table dynamically
 *
 * 4. TO COMPLETE MIGRATION:
 *    - Activate "MultiSide Aroi Integration" plugin
 *    - Replace this file with old functions.php
 *    - Verify in Admin → Aroi Config that site is detected
 *    - Test checkout page for pickup time selector
 *    - Test order creation and payment flow
 *
 * 5. IF ISSUES OCCUR:
 *    - Check wp-content/debug.log for errors
 *    - Verify _apningstid table has entry for your site
 *    - Check Admin → Aroi Config for configuration status
 *    - Ensure no duplicate hooks between theme and plugin
 */
