<?php
/**
 * Product Addons Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Product_Addons {
    
    /**
     * Update product addons from DSK format
     */
    public static function update_product_addons($post_id, $post, $update) {
        // Only process published products
        if ($post->post_status != 'publish' || $post->post_type != 'product') {
            return;
        }

        // Get WooCommerce product
        $product = wc_get_product($post);
        if (!$product) {
            return;
        }
        
        // Check if already updated
        $updated_status = get_post_meta($post_id, 'dsk_addons_updated', true);
        
        // Get product addons in DSK format
        $product_addons = self::get_dsk_product_addons($post_id);
        
        if (!empty($product_addons)) {
            // Update the product addons meta
            update_post_meta($post_id, '_product_addons', $product_addons);
            update_post_meta($post_id, 'dsk_addons_updated', 'yes');
        } else {
            update_post_meta($post_id, 'dsk_addons_updated', 'no');
        }
    }
    
    /**
     * Convert DSK addons format to WooCommerce Product Addons format
     */
    public static function get_dsk_product_addons($product_id) {
        $product_addons = array();
        
        // Get DSK addons
        $product_addons_dsk = get_post_meta($product_id, 'dsk_addons', true);
        $product_addons_dsk = json_decode($product_addons_dsk, true);
        
        if (empty($product_addons_dsk)) {
            return $product_addons;
        }
        
        $addons_index = 0;
        
        foreach ($product_addons_dsk as $key => $addon) {
            $addon_options = array();
            
            foreach ($addon as $addon_key => $addon_value) {
                $label = sanitize_text_field(stripslashes($addon_key));
                $price = '';
                
                if (!empty($addon_value)) {
                    $price = wc_format_decimal(sanitize_text_field(stripslashes($addon_value)));
                }
                
                $addon_options[] = array(
                    'label'   => $label,
                    'image'   => '',
                    'price'   => $price,
                    'min'     => '',
                    'max'     => '',
                    'default' => '',
                );
            }
            
            // Check if addon is required (has * in name)
            $addon_required = (strpos($key, '*') !== false) ? 1 : 0;
            
            // Clean addon name
            $addon_name = str_replace('*', '', $key);
            
            $data = array(
                'name'        => sanitize_text_field(stripslashes($addon_name)),
                'title_format' => 'label',
                'description_enable' => 0,
                'description' => '',
                'type'        => 'radiobutton',
                'display'     => 'select',
                'position'    => absint($addons_index),
                'required'    => $addon_required,
                'restrictions' => 0,
                'restrictions_type' => 'any_text',
                'adjust_price' => 1,
                'price_type'  => 'flat_fee',
                'min'         => '',
                'max'         => '',
                'options'     => $addon_options,
            );
            
            $product_addons[] = $data;
            $addons_index++;
        }
        
        // Sort by position
        usort($product_addons, array(__CLASS__, 'sort_addons_by_position'));
        
        return $product_addons;
    }
    
    /**
     * Sort addons by position
     */
    public static function sort_addons_by_position($a, $b) {
        if ($a['position'] == $b['position']) {
            return 0;
        }
        return ($a['position'] < $b['position']) ? -1 : 1;
    }
    
    /**
     * Migrate all products with DSK addons
     */
    public static function migrate_all_dsk_addons() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'dsk_addons',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $products = get_posts($args);
        $migrated = 0;
        
        foreach ($products as $product_post) {
            self::update_product_addons($product_post->ID, $product_post, true);
            $migrated++;
        }
        
        return $migrated;
    }
    
    /**
     * Check if product has DSK addons
     */
    public static function has_dsk_addons($product_id) {
        $dsk_addons = get_post_meta($product_id, 'dsk_addons', true);
        return !empty($dsk_addons);
    }
    
    /**
     * Convert WooCommerce addons back to DSK format
     */
    public static function convert_to_dsk_format($product_addons) {
        $dsk_format = array();
        
        foreach ($product_addons as $addon) {
            $addon_name = $addon['name'];
            
            // Add * if required
            if (!empty($addon['required'])) {
                $addon_name .= '*';
            }
            
            $dsk_addon = array();
            
            foreach ($addon['options'] as $option) {
                $dsk_addon[$option['label']] = $option['price'];
            }
            
            $dsk_format[$addon_name] = $dsk_addon;
        }
        
        return json_encode($dsk_format);
    }
}