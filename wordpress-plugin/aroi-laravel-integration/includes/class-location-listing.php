<?php
/**
 * Location Listing Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Location_Listing {
    
    /**
     * Get all locations from Laravel API
     */
    public static function get_all_locations() {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        
        // Site IDs for all locations as defined in CLAUDE.md
        $site_ids = array(7, 4, 6, 5, 10, 11, 12);
        $locations = array();
        
        foreach ($site_ids as $site_id) {
            $location_data = $api->get("/wordpress/location/{$site_id}");
            $opening_hours = $api->get("/wordpress/location/{$site_id}/opening-hours");
            $is_open = $api->get("/wordpress/location/{$site_id}/is-open", false); // Don't cache
            
            if ($location_data) {
                $locations[] = array_merge(
                    $location_data,
                    array(
                        'opening_hours' => $opening_hours,
                        'is_open_data' => $is_open
                    )
                );
            }
        }
        
        return $locations;
    }
    
    /**
     * Shortcode for displaying all locations as cards
     */
    public static function shortcode_location_cards($atts) {
        $atts = shortcode_atts(array(
            'columns' => '3',
            'show_map' => 'yes',
            'show_hours' => 'yes',
            'show_phone' => 'yes',
            'show_status' => 'yes'
        ), $atts);
        
        $locations = self::get_all_locations();
        
        if (empty($locations)) {
            return '<p>' . __('Ingen lokasjoner funnet', 'aroi-laravel') . '</p>';
        }
        
        // Sort locations by name
        usort($locations, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        ob_start();
        ?>
        <div class="aroi-locations-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($locations as $location): 
                if (!$location['active']) continue; // Skip inactive locations
                
                $is_open = $location['is_open_data']['is_open'] ?? false;
                $status_message = $location['is_open_data']['message'] ?? '';
                $open_time = $location['opening_hours']['open_time'] ?? '';
                $close_time = $location['opening_hours']['close_time'] ?? '';
                $map_address = urlencode($location['address']);
            ?>
                <div class="aroi-location-card <?php echo $is_open ? 'location-open' : 'location-closed'; ?>" data-site-id="<?php echo esc_attr($location['site_id']); ?>">
                    <a href="<?php echo esc_url($location['url']); ?>" class="location-card-link">
                        <div class="location-card-header">
                            <h3 class="location-name"><?php echo esc_html($location['name']); ?></h3>
                            <?php if ($atts['show_status'] === 'yes'): ?>
                                <div class="location-status">
                                    <span class="status-indicator <?php echo $is_open ? 'status-open' : 'status-closed'; ?>"></span>
                                    <span class="status-text"><?php echo esc_html($status_message); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="location-card-body">
                            <?php if ($atts['show_phone'] === 'yes' && !empty($location['phone'])): ?>
                                <div class="location-info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo esc_html($location['phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($location['address'])): ?>
                                <div class="location-info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo esc_html($location['address']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_hours'] === 'yes' && $open_time && $close_time): ?>
                                <div class="location-info-item">
                                    <i class="far fa-clock"></i>
                                    <span><?php echo esc_html($open_time); ?> - <?php echo esc_html($close_time); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_map'] === 'yes' && !empty($location['address'])): ?>
                                <div class="location-map">
                                    <iframe 
                                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo $map_address; ?>&zoom=15"
                                        width="100%"
                                        height="200"
                                        style="border:0;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="location-card-footer">
                            <span class="location-link-text"><?php _e('Bestill fra denne lokasjonen', 'aroi-laravel'); ?> <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying a single location card
     */
    public static function shortcode_single_location($atts) {
        $atts = shortcode_atts(array(
            'site' => '',
            'show_map' => 'yes',
            'show_hours' => 'yes',
            'show_phone' => 'yes',
            'show_status' => 'yes'
        ), $atts);
        
        if (empty($atts['site'])) {
            return '<p>' . __('Vennligst spesifiser en lokasjon', 'aroi-laravel') . '</p>';
        }
        
        $site_id = intval($atts['site']);
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        
        $location = $api->get("/wordpress/location/{$site_id}");
        $opening_hours = $api->get("/wordpress/location/{$site_id}/opening-hours");
        $is_open_data = $api->get("/wordpress/location/{$site_id}/is-open", false);
        
        if (!$location || !$location['active']) {
            return '<p>' . __('Lokasjon ikke funnet', 'aroi-laravel') . '</p>';
        }
        
        $is_open = $is_open_data['is_open'] ?? false;
        $status_message = $is_open_data['message'] ?? '';
        $open_time = $opening_hours['open_time'] ?? '';
        $close_time = $opening_hours['close_time'] ?? '';
        $map_address = urlencode($location['address']);
        
        ob_start();
        ?>
        <div class="aroi-single-location <?php echo $is_open ? 'location-open' : 'location-closed'; ?>">
            <div class="location-header">
                <h2><?php echo esc_html($location['name']); ?></h2>
                <?php if ($atts['show_status'] === 'yes'): ?>
                    <div class="location-status large">
                        <span class="status-indicator <?php echo $is_open ? 'status-open' : 'status-closed'; ?>"></span>
                        <span class="status-text"><?php echo esc_html($status_message); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="location-details">
                <div class="location-info">
                    <?php if ($atts['show_phone'] === 'yes' && !empty($location['phone'])): ?>
                        <div class="info-row">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr($location['phone']); ?>"><?php echo esc_html($location['phone']); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['address'])): ?>
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo esc_html($location['address']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_hours'] === 'yes' && $open_time && $close_time): ?>
                        <div class="info-row">
                            <i class="far fa-clock"></i>
                            <span><?php _e('I dag:', 'aroi-laravel'); ?> <?php echo esc_html($open_time); ?> - <?php echo esc_html($close_time); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($atts['show_map'] === 'yes' && !empty($location['address'])): ?>
                    <div class="location-map-large">
                        <iframe 
                            src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo $map_address; ?>&zoom=15"
                            width="100%"
                            height="400"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                <?php endif; ?>
                
                <div class="location-action">
                    <a href="<?php echo esc_url($location['url']); ?>" class="button button-primary">
                        <?php _e('Bestill mat fra denne lokasjonen', 'aroi-laravel'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}